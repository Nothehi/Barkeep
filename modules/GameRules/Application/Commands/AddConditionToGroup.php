<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Models\ConditionGroup;
use Modules\GameRules\Domain\Models\ConditionGroupCondition;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Put a condition into a group.
 *
 * The condition is resolved through the group's own rule set, so a group can
 * never combine sentences from two different games.
 *
 * Adding one that is already there is a no-op rather than an error. It is a
 * double-click, not a mistake worth a page about — and listing a condition twice
 * under `and` says nothing new, while under `or` it says nothing at all.
 */
final class AddConditionToGroup
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleCatalogue $catalogue,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, ConditionGroup $group, string $conditionId): ConditionGroup
    {
        $ruleSet = $group->ruleSet;

        if ($ruleSet === null) {
            return $group;
        }

        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        $condition = $this->catalogue->conditionOf($ruleSet, $conditionId);

        if ($this->structure->groupHasCondition($group, $condition->getKey())) {
            return $group;
        }

        $membership = new ConditionGroupCondition;
        $membership->condition_group_id = $group->getKey();
        $membership->condition_id = $condition->getKey();
        $membership->position = $this->structure->countGroupMembers($group);
        $membership->save();

        return $group->load('conditions');
    }
}
