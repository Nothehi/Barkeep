<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\ConditionGroupData;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Exceptions\RuleNameIsTaken;
use Modules\GameRules\Domain\Models\ConditionGroup;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Rename a group, or switch it between "all of these" and "any of these".
 *
 * Switching the operator is the change that matters. A group of three conditions
 * means something quite different under `or`, and there is deliberately no way to
 * express "two of these" — section 19 keeps the vocabulary at two words.
 */
final class UpdateConditionGroup
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, ConditionGroup $group, ConditionGroupData $data): ConditionGroup
    {
        $ruleSet = $group->ruleSet;

        if ($ruleSet === null) {
            return $group;
        }

        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        if ($data->name !== null && $data->name !== $group->name) {
            if ($this->structure->ruleSetHasConditionGroupNamed($ruleSet, $data->name, $group->getKey())) {
                throw RuleNameIsTaken::forConditionGroup($data->name);
            }

            $group->name = $data->name;
        }

        if ($data->sent('description')) {
            $group->description = $data->description;
        }

        if ($data->logicOperator !== null) {
            $group->logic_operator = $data->logicOperator;
        }

        $group->save();

        return $group;
    }
}
