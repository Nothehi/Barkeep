<?php

namespace Modules\GameRules\Infrastructure\Analysis;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameRules\Domain\Enums\RuleEntityType;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\PhaseTransition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\RuleGraph;
use Modules\GameRules\Domain\ValueObjects\RuleGraphEdge;
use Modules\GameRules\Domain\ValueObjects\RuleGraphNode;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * The flow of a game, assembled from its phases and transitions.
 *
 *     START
 *       ↓
 *     Setup
 *       ↓
 *     Round start
 *       ↓
 *     Action phase   ├── Build
 *       ↓            ├── Move
 *     Resolution     └── Trade
 *       ↓
 *     Cleanup
 *       ↓
 *     Game end
 *
 * Section 32 of the brief, and the constraint in it that matters most: *no
 * separate graph database*. A rule set has a handful of phases and a dozen edges,
 * so the whole thing is built in one pass over records the dashboard has already
 * loaded. There is no graph store to keep in step and nothing to reindex when a
 * phase is renamed.
 *
 * The graph is read-only. Editing happens in the phase designer, which works with
 * phases and transitions; this is what those *are* when drawn. That is why nodes
 * carry already-worded labels rather than models.
 *
 * ## Two things the graph knows that the records do not
 *
 * The `START` node is synthetic. Nothing in the database corresponds to it, and it
 * is drawn even when no phase is marked as setup, because a diagram whose first
 * box is "Action phase" quietly implies the game begins there.
 *
 * `unreachable` is computed here rather than by the validator, and the validator
 * asks *this* for the answer. Both would otherwise walk the same edges in two
 * places, and the day one of them learned about a new kind of edge the other would
 * start disagreeing with it.
 */
final class RuleGraphBuilder
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly CycleDetector $cycles,
    ) {}

    /**
     * Draw the flow of the given rule set.
     */
    public function build(RuleSet $ruleSet): RuleGraph
    {
        $phases = $this->structure->phasesOf($ruleSet);

        if ($phases->isEmpty()) {
            return RuleGraph::empty();
        }

        $transitions = $this->structure->transitionsOf($ruleSet);
        $actionsByPhase = $this->actionNamesByPhase($ruleSet);

        $nodes = [RuleGraphNode::start()];

        foreach ($phases as $phase) {
            $nodes[] = $this->nodeFor($phase, $actionsByPhase);
        }

        $edges = $this->edgesFor($phases, $transitions);

        return new RuleGraph(
            nodes: $nodes,
            edges: $edges,
            unreachable: $this->unreachable($nodes, $edges),
        );
    }

    /**
     * What players may do during each phase, by phase id.
     *
     * Names rather than models, and a plain array rather than a grouped
     * collection. The graph only needs the labels, and reducing to strings here
     * keeps the node builder from being handed anything it could accidentally
     * query.
     *
     * @return array<string, list<string>>
     */
    private function actionNamesByPhase(RuleSet $ruleSet): array
    {
        $byPhase = [];

        foreach ($this->structure->actionsOf($ruleSet) as $action) {
            if ($action->phase_id === null) {
                continue;
            }

            $byPhase[$action->phase_id][] = $action->name;
        }

        return $byPhase;
    }

    /**
     * One phase, as a box.
     *
     * The actions taken during the phase travel with it as labels rather than as
     * nodes of their own. That is a deliberate reading of what the diagram is for:
     * play moves between phases, and drawing "Build", "Move" and "Trade" as boxes
     * would suggest it moves between *those* — which is what a turn is not.
     *
     * @param  array<string, list<string>>  $actionsByPhase
     */
    private function nodeFor(GamePhase $phase, array $actionsByPhase): RuleGraphNode
    {
        $key = (string) $phase->getKey();

        return new RuleGraphNode(
            key: $key,
            entityType: RuleEntityType::Phase,
            entityId: $key,
            label: $phase->name,
            detail: $phase->phase_type->label(),
            isEntry: $phase->isEntry(),
            isTerminal: $phase->isTerminal(),
            actions: $actionsByPhase[$key] ?? [],
        );
    }

    /**
     * Every arrow, including the one into the first phase.
     *
     * The implicit start edge points at whichever phase claims to be setup, or —
     * when none does — at whichever comes first in the designer's order. The second
     * case is a guess and is marked implicit so the interface can draw it faintly:
     * somebody should be able to tell what they wrote from what was inferred for
     * them.
     *
     * @param  Collection<int, GamePhase>  $phases
     * @param  Collection<int, PhaseTransition>  $transitions
     * @return list<RuleGraphEdge>
     */
    private function edgesFor(Collection $phases, Collection $transitions): array
    {
        $edges = [];

        $entry = $phases->first(fn (GamePhase $phase): bool => $phase->isEntry()) ?? $phases->first();

        if ($entry instanceof GamePhase) {
            $edges[] = RuleGraphEdge::implicit('start', (string) $entry->getKey());
        }

        foreach ($transitions as $transition) {
            $edges[] = new RuleGraphEdge(
                from: $transition->from_phase_id,
                to: $transition->to_phase_id,
                label: $this->labelFor($transition),
                entityId: (string) $transition->getKey(),
            );
        }

        return $edges;
    }

    /**
     * What an arrow says, if it says anything.
     *
     * The condition's own sentence, then the trigger's name, then nothing. An
     * unguarded transition is the ordinary case in a board game — the action phase
     * simply ends and resolution begins — and labelling it "always" would add a
     * word to every arrow to say what the absence of one already says.
     */
    private function labelFor(PhaseTransition $transition): ?string
    {
        if ($transition->condition !== null) {
            return $transition->condition->statement();
        }

        if ($transition->trigger !== null) {
            return $transition->trigger->name;
        }

        return null;
    }

    /**
     * The phases play never arrives at.
     *
     * A phase with no path from `START` is one a designer has written and then
     * orphaned — usually by renaming the transition that led to it, or by never
     * drawing one. The validator reports each as a warning rather than an error,
     * because a phase written before the transitions around it is the ordinary
     * middle of the work.
     *
     * @param  list<RuleGraphNode>  $nodes
     * @param  list<RuleGraphEdge>  $edges
     * @return list<string>
     */
    private function unreachable(array $nodes, array $edges): array
    {
        $adjacency = [];

        foreach ($edges as $edge) {
            $adjacency[$edge->from][] = $edge->to;
        }

        $reached = $this->cycles->reachableFrom($adjacency, ['start']);

        $orphans = [];

        foreach ($nodes as $node) {
            if ($node->key !== 'start' && ! in_array($node->key, $reached, strict: true)) {
                $orphans[] = $node->key;
            }
        }

        return $orphans;
    }
}
