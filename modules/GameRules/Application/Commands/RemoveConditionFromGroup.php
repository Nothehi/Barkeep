<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Models\ConditionGroup;
use Modules\GameRules\Domain\Models\ConditionGroupCondition;
use Modules\Identity\Domain\Models\User;

/**
 * Take a condition out of a group.
 *
 * Acts on the membership rather than on the condition, because the same condition
 * may be in several groups and removing it from one must not touch the others.
 * That is why the membership row has a uuid at all.
 */
final class RemoveConditionFromGroup
{
    public function __construct(private readonly RuleWorkGuard $guard) {}

    public function handle(User $actor, ConditionGroup $group, ConditionGroupCondition $membership): void
    {
        $ruleSet = $group->ruleSet;

        if ($ruleSet !== null) {
            $this->guard->ensureRuleSetAcceptsChanges($ruleSet);
        }

        $membership->delete();
    }
}
