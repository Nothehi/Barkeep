<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\ActionLineData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Events\EconomyActionUpdated;
use Modules\GameEconomy\Domain\Models\ActionCost;
use Modules\Identity\Domain\Models\User;

/**
 * Retune what an action costs.
 *
 * The resource is not editable here. Changing which resource a line is about is
 * not an edit to the price — it is removing one cost and adding another, and
 * letting a PATCH do it would make the unique constraint on (action, resource)
 * reachable through a route that never mentions it.
 */
final class UpdateActionCost
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $actor, ActionCost $cost, ActionLineData $data): ActionCost
    {
        $profile = $cost->action?->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        if ($data->amount !== null) {
            $cost->amount = $data->amount->absolute();
        }

        if ($data->isVariable !== null) {
            $cost->is_variable = $data->isVariable;
        }

        foreach (['min_amount' => $data->minAmount, 'max_amount' => $data->maxAmount] as $field => $value) {
            if ($data->sent($field)) {
                $cost->{$field} = $value;
            }
        }

        $changed = $cost->isDirty();

        $cost->save();

        if ($changed) {
            event(new EconomyActionUpdated(
                actionId: $cost->action_id,
                profileId: $cost->action->balance_profile_id,
                changedFields: ['costs'],
            ));
        }

        return $cost;
    }
}
