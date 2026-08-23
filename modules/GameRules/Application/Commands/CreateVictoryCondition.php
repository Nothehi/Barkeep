<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\OutcomeData;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\VictoryConditionCreated;
use Modules\GameRules\Domain\Exceptions\RuleNameIsTaken;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\Models\VictoryCondition;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Record a way to win the game.
 *
 *     First player to reach 20 victory points.
 *     Highest score after eight rounds.
 *     Control three territories at the end of a round.
 *
 * The condition is optional, and most of these are written without one:
 * "whoever has the most points" goes in on day one and the sentence that measures it comes
 * later, if at all. The validator says so and nothing refuses it — an outcome a
 * studio has stated but not yet formalised is still the most important thing in
 * the rule set.
 *
 * When a condition is given it is resolved through the rule set, so an outcome
 * can never be measured by a sentence from another game.
 */
final class CreateVictoryCondition
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleCatalogue $catalogue,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleSet $ruleSet, OutcomeData $data): VictoryCondition
    {
        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        $name = $data->name ?? '';

        if ($this->structure->ruleSetHasVictoryConditionNamed($ruleSet, $name)) {
            throw RuleNameIsTaken::forVictoryCondition($name);
        }

        $condition = $data->conditionId === null
            ? null
            : $this->catalogue->conditionOf($ruleSet, $data->conditionId);

        $outcome = new VictoryCondition;

        $outcome->fill([
            'name' => $name,
            'description' => $data->description,
        ]);

        $outcome->rule_set_id = $ruleSet->getKey();
        $outcome->condition_id = $condition?->getKey();
        $outcome->priority = $data->priority ?? $ruleSet->victoryConditions()->count();

        $outcome->save();

        $outcome->setRelation('ruleSet', $ruleSet);

        event(new VictoryConditionCreated(
            outcomeId: $outcome->getKey(),
            ruleSetId: $ruleSet->getKey(),
            name: $outcome->name,
            isMeasurable: $outcome->isMeasurable(),
        ));

        return $outcome;
    }
}
