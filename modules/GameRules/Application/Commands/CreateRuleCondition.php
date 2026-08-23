<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\ConditionData;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Enums\ConditionOperator;
use Modules\GameRules\Domain\Enums\ConditionType;
use Modules\GameRules\Domain\Events\RuleConditionCreated;
use Modules\GameRules\Domain\Exceptions\RuleNameIsTaken;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Name a reusable logical requirement.
 *
 *     [Score] [is at least] [20]
 *
 * The name is the identity, because these are pointed at in prose: a transition
 * guarded by "all players have passed" is readable in a way one guarded by a uuid
 * is not. A duplicate name is refused rather than silently disambiguated.
 *
 * Nothing validates the value against the operator here. That pairing is a
 * *finding* — "is at least blue" is a sentence somebody typed mid-thought — and
 * refusing to save it would stop them getting to the end of the sentence.
 */
final class CreateRuleCondition
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleSet $ruleSet, ConditionData $data): RuleCondition
    {
        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        $name = $data->name ?? '';

        if ($this->structure->ruleSetHasConditionNamed($ruleSet, $name)) {
            throw RuleNameIsTaken::forCondition($name);
        }

        $condition = new RuleCondition;

        $condition->fill([
            'name' => $name,
            'description' => $data->description,
            'value' => $data->value,
        ]);

        $condition->rule_set_id = $ruleSet->getKey();
        $condition->condition_type = $data->conditionType ?? ConditionType::default();
        $condition->operator = $data->operator ?? ConditionOperator::default();

        $condition->save();

        $condition->setRelation('ruleSet', $ruleSet);

        event(new RuleConditionCreated(
            conditionId: $condition->getKey(),
            ruleSetId: $ruleSet->getKey(),
            name: $condition->name,
        ));

        return $condition;
    }
}
