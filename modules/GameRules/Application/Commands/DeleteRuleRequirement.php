<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Models\RuleRequirement;
use Modules\Identity\Domain\Models\User;

/**
 * Remove a gate from a rule or an action.
 *
 * The action survives, and the validator will report it as one anybody can always
 * take if this was its last requirement.
 */
final class DeleteRuleRequirement
{
    public function __construct(private readonly RuleWorkGuard $guard) {}

    public function handle(User $actor, RuleRequirement $requirement): void
    {
        $ruleSet = $requirement->ruleSet;

        if ($ruleSet !== null) {
            $this->guard->ensureRuleSetAcceptsChanges($ruleSet);
        }

        $requirement->delete();
    }
}
