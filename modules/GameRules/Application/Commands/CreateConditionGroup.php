<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\ConditionGroupData;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Enums\LogicOperator;
use Modules\GameRules\Domain\Exceptions\RuleNameIsTaken;
use Modules\GameRules\Domain\Models\ConditionGroup;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Combine several conditions under one operator.
 *
 * Created empty, and filled by {@see AddConditionToGroup}. Two steps rather than
 * one because the conditions usually exist already and picking them is a
 * different gesture from naming the group — and because a create form carrying a
 * list would have to solve reordering as well.
 */
final class CreateConditionGroup
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleSet $ruleSet, ConditionGroupData $data): ConditionGroup
    {
        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        $name = $data->name ?? '';

        if ($this->structure->ruleSetHasConditionGroupNamed($ruleSet, $name)) {
            throw RuleNameIsTaken::forConditionGroup($name);
        }

        $group = new ConditionGroup;

        $group->fill([
            'name' => $name,
            'description' => $data->description,
        ]);

        $group->rule_set_id = $ruleSet->getKey();
        $group->logic_operator = $data->logicOperator ?? LogicOperator::default();

        $group->save();

        $group->setRelation('ruleSet', $ruleSet);

        return $group;
    }
}
