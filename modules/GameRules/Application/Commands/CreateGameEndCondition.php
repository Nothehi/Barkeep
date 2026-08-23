<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\OutcomeData;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\GameEndConditionCreated;
use Modules\GameRules\Domain\Exceptions\RuleNameIsTaken;
use Modules\GameRules\Domain\Models\GameEndCondition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Record something that brings the game to a close.
 *
 *     Round eight ends.
 *     The main deck runs out.
 *     A player crosses the victory threshold.
 *
 * Distinct from a victory condition: this stops the game, it does not say who
 * won. Most point-salad games need both, and a rule set that only recorded
 * victory conditions would have nowhere to put the first.
 *
 * The condition is optional, and most of these are written without one:
 * "the game ends when the deck runs out" goes in on day one and the sentence that measures it comes
 * later, if at all. The validator says so and nothing refuses it — an outcome a
 * studio has stated but not yet formalised is still the most important thing in
 * the rule set.
 *
 * When a condition is given it is resolved through the rule set, so an outcome
 * can never be measured by a sentence from another game.
 */
final class CreateGameEndCondition
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleCatalogue $catalogue,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleSet $ruleSet, OutcomeData $data): GameEndCondition
    {
        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        $name = $data->name ?? '';

        if ($this->structure->ruleSetHasGameEndConditionNamed($ruleSet, $name)) {
            throw RuleNameIsTaken::forGameEndCondition($name);
        }

        $condition = $data->conditionId === null
            ? null
            : $this->catalogue->conditionOf($ruleSet, $data->conditionId);

        $outcome = new GameEndCondition;

        $outcome->fill([
            'name' => $name,
            'description' => $data->description,
        ]);

        $outcome->rule_set_id = $ruleSet->getKey();
        $outcome->condition_id = $condition?->getKey();
        $outcome->priority = $data->priority ?? $ruleSet->endConditions()->count();

        $outcome->save();

        $outcome->setRelation('ruleSet', $ruleSet);

        event(new GameEndConditionCreated(
            outcomeId: $outcome->getKey(),
            ruleSetId: $ruleSet->getKey(),
            name: $outcome->name,
            isMeasurable: $outcome->isMeasurable(),
        ));

        return $outcome;
    }
}
