<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\PhaseTransitionDeleted;
use Modules\GameRules\Domain\Models\PhaseTransition;
use Modules\Identity\Domain\Models\User;

/**
 * Remove a way for play to advance.
 *
 * Both phases survive. Only the claim that play moves between them goes away —
 * after which the validator will report the origin as having no exit, if this was
 * its last one.
 */
final class DeletePhaseTransition
{
    public function __construct(private readonly RuleWorkGuard $guard) {}

    public function handle(User $actor, PhaseTransition $transition): void
    {
        $ruleSet = $transition->ruleSet;

        if ($ruleSet !== null) {
            $this->guard->ensureRuleSetAcceptsChanges($ruleSet);
        }

        $transitionId = $transition->getKey();
        $ruleSetId = $transition->rule_set_id;
        $fromPhaseId = $transition->from_phase_id;

        $transition->delete();

        event(new PhaseTransitionDeleted(
            transitionId: $transitionId,
            ruleSetId: $ruleSetId,
            fromPhaseId: $fromPhaseId,
        ));
    }
}
