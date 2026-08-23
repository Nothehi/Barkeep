<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\ConditionData;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\RuleConditionUpdated;
use Modules\GameRules\Domain\Exceptions\RuleNameIsTaken;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Change what a condition measures, how, or against what.
 *
 * Worth more attention than its size suggests: everything pointing at the
 * condition — transitions, victory conditions, end conditions, groups — now means
 * something different. That is the point of naming conditions rather than
 * inlining them, and it is why the event says the condition changed rather than
 * naming the places affected: a consumer that cares can look them up, and this
 * command should not have to know who they are.
 */
final class UpdateRuleCondition
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleCondition $condition, ConditionData $data): RuleCondition
    {
        $ruleSet = $condition->ruleSet;

        if ($ruleSet === null) {
            return $condition;
        }

        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        if ($data->name !== null && $data->name !== $condition->name) {
            if ($this->structure->ruleSetHasConditionNamed($ruleSet, $data->name, $condition->getKey())) {
                throw RuleNameIsTaken::forCondition($data->name);
            }

            $condition->name = $data->name;
        }

        if ($data->sent('description')) {
            $condition->description = $data->description;
        }

        if ($data->conditionType !== null) {
            $condition->condition_type = $data->conditionType;
        }

        if ($data->operator !== null) {
            $condition->operator = $data->operator;
        }

        if ($data->sent('value')) {
            $condition->value = $data->value;
        }

        $changed = array_keys($condition->getDirty());

        $condition->save();

        if ($changed !== []) {
            event(new RuleConditionUpdated(
                conditionId: $condition->getKey(),
                ruleSetId: $condition->rule_set_id,
                changedFields: $changed,
            ));
        }

        return $condition;
    }
}
