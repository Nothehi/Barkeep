<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\PhaseTransitionData;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\PhaseTransitionUpdated;
use Modules\GameRules\Domain\Exceptions\InvalidPhaseTransition;
use Modules\GameRules\Domain\Models\PhaseTransition;
use Modules\Identity\Domain\Models\User;

/**
 * Change where a transition leads, what guards it, or when it is considered.
 *
 * The order matters more than it looks: "if somebody has won, go to game end;
 * otherwise back to round start" is two edges out of one phase whose position is
 * the rule. Reordering them changes the game.
 */
final class UpdatePhaseTransition
{
    public function __construct(
        private readonly RuleCatalogue $catalogue,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, PhaseTransition $transition, PhaseTransitionData $data): PhaseTransition
    {
        $ruleSet = $transition->ruleSet;

        if ($ruleSet === null) {
            return $transition;
        }

        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        if ($data->fromPhaseId !== null) {
            $transition->from_phase_id = $this->catalogue
                ->phaseOf($ruleSet, $data->fromPhaseId, 'from_phase_id')
                ->getKey();
        }

        if ($data->toPhaseId !== null) {
            $transition->to_phase_id = $this->catalogue
                ->phaseOf($ruleSet, $data->toPhaseId, 'to_phase_id')
                ->getKey();
        }

        if ($transition->from_phase_id === $transition->to_phase_id) {
            throw InvalidPhaseTransition::loopsOnItself($transition->from_phase_id);
        }

        if ($data->sent('condition_id')) {
            $transition->condition_id = $data->conditionId === null
                ? null
                : $this->catalogue->conditionOf($ruleSet, $data->conditionId)->getKey();
        }

        if ($data->sent('trigger_id')) {
            $transition->trigger_id = $data->triggerId === null
                ? null
                : $this->catalogue->triggerOf($ruleSet, $data->triggerId)->getKey();
        }

        if ($data->position !== null) {
            $transition->position = $data->position;
        }

        $changed = array_keys($transition->getDirty());

        $transition->save();

        if ($changed !== []) {
            event(new PhaseTransitionUpdated(
                transitionId: $transition->getKey(),
                ruleSetId: $transition->rule_set_id,
                changedFields: $changed,
            ));
        }

        return $transition;
    }
}
