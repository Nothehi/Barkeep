<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Events\ScenarioVariableChanged;
use Modules\GameEconomy\Domain\Models\ScenarioVariable;
use Modules\Identity\Domain\Models\User;

/**
 * Stop a hypothetical stating a value differently.
 *
 * The variable reverts to the profile's own number — which it never left, since
 * the override was a separate row all along. Nothing is restored here because
 * nothing was overwritten.
 */
final class RemoveScenarioVariable
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $actor, ScenarioVariable $override): void
    {
        $scenario = $override->scenario;

        if ($scenario !== null) {
            $this->guard->ensureScenarioIsModifiable($scenario);
        }

        $scenarioId = $override->scenario_id;
        $profileId = $scenario->balance_profile_id;
        $variableId = $override->balance_variable_id;

        $override->delete();

        event(new ScenarioVariableChanged(
            scenarioId: $scenarioId,
            profileId: $profileId,
            variableId: $variableId,
            wasRemoved: true,
        ));
    }
}
