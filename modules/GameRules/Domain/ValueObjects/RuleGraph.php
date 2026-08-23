<?php

namespace Modules\GameRules\Domain\ValueObjects;

/**
 * The flow of a game, as the rule set describes it.
 *
 *     START → Setup → Round start → Action phase → Resolution → Cleanup
 *                          ↑                                       │
 *                          └───────────── no ──── [end?] ──────────┘
 *                                                    │ yes
 *                                                    ↓
 *                                                 Game end
 *
 * Built from phases, transitions and the conditions they name — section 32 of
 * the brief. There is no graph store and no graph library: a rule set has a
 * handful of phases, so the whole thing is assembled in a single pass over
 * records already loaded for the dashboard.
 *
 * Read-only, in the strong sense. Nothing about this can be edited; the phase
 * designer edits phases and transitions, and the graph is what those *are* when
 * drawn. That is why nodes carry labels rather than models, and why a node
 * without an `entityId` is one nothing corresponds to.
 *
 * `unreachable` is computed here rather than by the validator so both agree by
 * construction: the validator asks the graph which phases play never arrives at,
 * instead of reimplementing the walk.
 */
final readonly class RuleGraph
{
    /**
     * @param  list<RuleGraphNode>  $nodes
     * @param  list<RuleGraphEdge>  $edges
     * @param  list<string>  $unreachable  keys of nodes no path from start reaches
     */
    public function __construct(
        public array $nodes,
        public array $edges,
        public array $unreachable = [],
    ) {}

    /**
     * The graph of a rule set with no phases in it.
     */
    public static function empty(): self
    {
        return new self(nodes: [RuleGraphNode::start()], edges: [], unreachable: []);
    }

    /**
     * Determine whether there is anything to draw beyond the start box.
     */
    public function isEmpty(): bool
    {
        return $this->edges === [] && count($this->nodes) <= 1;
    }

    /**
     * Determine whether play can reach the given node from the start.
     */
    public function reaches(string $key): bool
    {
        return ! in_array($key, $this->unreachable, strict: true);
    }
}
