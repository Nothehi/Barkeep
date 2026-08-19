<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\ScenarioVariableData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Application\Services\EconomyCatalogue;
use Modules\GameEconomy\Domain\Events\ScenarioVariableChanged;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\GameEconomy\Domain\Models\ScenarioVariable;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;
use Modules\Identity\Domain\Models\User;

/**
 * State a value differently under a hypothetical.
 *
 * "In Rich Economy, starting gold is 15."
 *
 * The single most important thing about this command is what it does not do:
 * it never writes to the variable. The override goes into its own table, so
 * "a scenario never modifies the base value" is a property of where the data
 * lands rather than a rule this code has to remember — there is no line here
 * that could break it, and no future edit that could introduce one without
 * moving the write to a different table.
 *
 * Setting a value twice replaces the first, because a scenario states one value
 * for a variable. Two rows would be two answers with no way to choose between
 * them, which is what the unique index refuses.
 */
final class SetScenarioVariable
{
    public function __construct(
        private readonly EconomyCatalogue $catalogue,
        private readonly EconomyRepository $economy,
        private readonly BalanceWorkGuard $guard,
    ) {}

    public function handle(User $actor, BalanceScenario $scenario, ScenarioVariableData $data): ScenarioVariable
    {
        $this->guard->ensureScenarioIsModifiable($scenario);

        $variable = $this->catalogue->variableForScenario($scenario, $data->balanceVariableId);

        $override = $this->economy->findOverrideForVariable($scenario, $variable) ?? new ScenarioVariable;

        $override->scenario_id = $scenario->getKey();
        $override->balance_variable_id = $variable->getKey();
        $override->value = $data->value;

        $override->save();

        $override->setRelation('scenario', $scenario);
        $override->setRelation('variable', $variable);

        event(new ScenarioVariableChanged(
            scenarioId: $scenario->getKey(),
            profileId: $scenario->balance_profile_id,
            variableId: $variable->getKey(),
            wasRemoved: false,
        ));

        return $override;
    }
}
