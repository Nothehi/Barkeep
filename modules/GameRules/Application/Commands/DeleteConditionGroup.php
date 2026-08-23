<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Models\ConditionGroup;
use Modules\Identity\Domain\Models\User;

/**
 * Remove a grouping.
 *
 * The conditions in it survive — they are named records in their own right, and a
 * designer dissolving a group has said nothing about wanting to lose the four
 * sentences inside it. Only the memberships go.
 */
final class DeleteConditionGroup
{
    public function __construct(private readonly RuleWorkGuard $guard) {}

    public function handle(User $actor, ConditionGroup $group): void
    {
        $ruleSet = $group->ruleSet;

        if ($ruleSet !== null) {
            $this->guard->ensureRuleSetAcceptsChanges($ruleSet);
        }

        $group->delete();
    }
}
