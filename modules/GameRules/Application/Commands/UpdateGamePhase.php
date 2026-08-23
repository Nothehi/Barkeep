<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\GamePhaseData;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\GamePhaseUpdated;
use Modules\GameRules\Domain\Exceptions\CircularRuleHierarchy;
use Modules\GameRules\Domain\Exceptions\RuleSlugIsTaken;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\ValueObjects\RuleSlug;
use Modules\GameRules\Infrastructure\Analysis\CycleDetector;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Rename a phase, retype it, move it in the turn structure, or retire it.
 *
 * Reparenting is checked for a cycle before the write, exactly as a rule's is: a
 * phase nested inside itself has no top, and the diagram, the validator and the
 * rulebook would all fail on it in different ways.
 *
 * Changing the type is more consequential than it looks. Marking a phase as an
 * end-game phase makes it terminal, which stops the validator asking it for an
 * exit and makes the graph draw it as a stopping point.
 */
final class UpdateGamePhase
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleCatalogue $catalogue,
        private readonly CycleDetector $cycles,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, GamePhase $phase, GamePhaseData $data): GamePhase
    {
        $ruleSet = $phase->ruleSet;

        if ($ruleSet === null) {
            return $phase;
        }

        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        if ($data->name !== null && $data->name !== $phase->name) {
            $slug = RuleSlug::fromName($data->name);

            if ($this->structure->ruleSetHasPhaseSlug($ruleSet, $slug, $phase->getKey())) {
                throw RuleSlugIsTaken::forPhase($slug);
            }

            $phase->name = $data->name;
            $phase->slug = $slug->value;
        }

        if ($data->sent('description')) {
            $phase->description = $data->description;
        }

        if ($data->sent('parent_phase_id')) {
            $phase->parent_phase_id = $this->resolveParent($phase, $data->parentPhaseId);
        }

        if ($data->phaseType !== null) {
            $phase->phase_type = $data->phaseType;
        }

        if ($data->status !== null) {
            $phase->status = $data->status;
        }

        if ($data->position !== null) {
            $phase->position = $data->position;
        }

        $changed = array_keys($phase->getDirty());

        $phase->save();

        if ($changed !== []) {
            event(new GamePhaseUpdated(
                phaseId: $phase->getKey(),
                ruleSetId: $phase->rule_set_id,
                changedFields: $changed,
            ));
        }

        return $phase;
    }

    /**
     * @throws CircularRuleHierarchy
     */
    private function resolveParent(GamePhase $phase, ?string $parentPhaseId): ?string
    {
        if ($parentPhaseId === null) {
            return null;
        }

        $ruleSet = $phase->ruleSet;

        if ($ruleSet === null) {
            return null;
        }

        $parent = $this->catalogue->phaseOf($ruleSet, $parentPhaseId, 'parent_phase_id');

        if ($this->cycles->wouldCreateCycle($this->structure->phaseParentMap($ruleSet), $phase->getKey(), $parent->getKey())) {
            throw CircularRuleHierarchy::forPhase($phase->getKey(), $parent->getKey());
        }

        return $parent->getKey();
    }
}
