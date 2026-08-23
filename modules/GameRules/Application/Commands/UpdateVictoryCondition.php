<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\OutcomeData;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Exceptions\RuleNameIsTaken;
use Modules\GameRules\Domain\Models\VictoryCondition;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Reword a way to win, attach a condition to it, or change when it is checked.
 *
 * The priority is the part that changes the game. Outcomes are checked in order
 * and the order settles ties, so moving one above another is a rule change rather
 * than a display preference.
 */
final class UpdateVictoryCondition
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleCatalogue $catalogue,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, VictoryCondition $outcome, OutcomeData $data): VictoryCondition
    {
        $ruleSet = $outcome->ruleSet;

        if ($ruleSet === null) {
            return $outcome;
        }

        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        if ($data->name !== null && $data->name !== $outcome->name) {
            if ($this->structure->ruleSetHasVictoryConditionNamed($ruleSet, $data->name, $outcome->getKey())) {
                throw RuleNameIsTaken::forVictoryCondition($data->name);
            }

            $outcome->name = $data->name;
        }

        if ($data->sent('description')) {
            $outcome->description = $data->description;
        }

        if ($data->sent('condition_id')) {
            $outcome->condition_id = $data->conditionId === null
                ? null
                : $this->catalogue->conditionOf($ruleSet, $data->conditionId)->getKey();
        }

        if ($data->priority !== null) {
            $outcome->priority = $data->priority;
        }

        $outcome->save();

        return $outcome;
    }
}
