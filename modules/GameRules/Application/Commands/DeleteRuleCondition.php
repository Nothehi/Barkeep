<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\RuleConditionDeleted;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\Identity\Domain\Models\User;

/**
 * Remove a named condition.
 *
 * Whatever pointed at it keeps its own row with the reference cleared, and the
 * validator reports the gap. A victory condition is not deleted because the
 * sentence that measured it was: "first to twenty points" is still the studio's
 * intention, and quietly removing it would throw away the goal along with the
 * measurement.
 *
 * Group memberships do cascade, because a membership is nothing without the
 * condition it is a membership of.
 */
final class DeleteRuleCondition
{
    public function __construct(private readonly RuleWorkGuard $guard) {}

    public function handle(User $actor, RuleCondition $condition): void
    {
        $ruleSet = $condition->ruleSet;

        if ($ruleSet !== null) {
            $this->guard->ensureRuleSetAcceptsChanges($ruleSet);
        }

        $conditionId = $condition->getKey();
        $ruleSetId = $condition->rule_set_id;
        $name = $condition->name;

        $condition->delete();

        event(new RuleConditionDeleted(
            conditionId: $conditionId,
            ruleSetId: $ruleSetId,
            name: $name,
        ));
    }
}
