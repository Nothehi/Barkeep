<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\DTOs\OutcomeData;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\DefeatConditionCreated;
use Modules\GameRules\Domain\Exceptions\RuleNameIsTaken;
use Modules\GameRules\Domain\Models\DefeatCondition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Record a way to be knocked out of the game.
 *
 *     Your health reaches zero.
 *     You have no legal action on your turn.
 *     Your team loses its last territory.
 *
 * The condition is optional, and most of these are written without one:
 * "you are out when you run out of ships" goes in on day one and the sentence that measures it comes
 * later, if at all. The validator says so and nothing refuses it — an outcome a
 * studio has stated but not yet formalised is still the most important thing in
 * the rule set.
 *
 * When a condition is given it is resolved through the rule set, so an outcome
 * can never be measured by a sentence from another game.
 */
final class CreateDefeatCondition
{
    public function __construct(
        private readonly RuleStructureRepository $structure,
        private readonly RuleCatalogue $catalogue,
        private readonly RuleWorkGuard $guard,
    ) {}

    public function handle(User $actor, RuleSet $ruleSet, OutcomeData $data): DefeatCondition
    {
        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        $name = $data->name ?? '';

        if ($this->structure->ruleSetHasDefeatConditionNamed($ruleSet, $name)) {
            throw RuleNameIsTaken::forDefeatCondition($name);
        }

        $condition = $data->conditionId === null
            ? null
            : $this->catalogue->conditionOf($ruleSet, $data->conditionId);

        $outcome = new DefeatCondition;

        $outcome->fill([
            'name' => $name,
            'description' => $data->description,
        ]);

        $outcome->rule_set_id = $ruleSet->getKey();
        $outcome->condition_id = $condition?->getKey();
        $outcome->priority = $data->priority ?? $ruleSet->defeatConditions()->count();

        $outcome->save();

        $outcome->setRelation('ruleSet', $ruleSet);

        event(new DefeatConditionCreated(
            outcomeId: $outcome->getKey(),
            ruleSetId: $ruleSet->getKey(),
            name: $outcome->name,
            isMeasurable: $outcome->isMeasurable(),
        ));

        return $outcome;
    }
}
