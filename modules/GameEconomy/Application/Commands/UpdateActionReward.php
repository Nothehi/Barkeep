<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\ActionLineData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Events\EconomyActionUpdated;
use Modules\GameEconomy\Domain\Models\ActionReward;
use Modules\Identity\Domain\Models\User;

/**
 * Retune what an action pays out.
 *
 * As with costs, the resource is not editable here — changing it is removing one
 * line and adding another.
 */
final class UpdateActionReward
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $actor, ActionReward $reward, ActionLineData $data): ActionReward
    {
        $profile = $reward->action?->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        if ($data->amount !== null) {
            $reward->amount = $data->amount->absolute();
        }

        if ($data->isVariable !== null) {
            $reward->is_variable = $data->isVariable;
        }

        foreach (['min_amount' => $data->minAmount, 'max_amount' => $data->maxAmount] as $field => $value) {
            if ($data->sent($field)) {
                $reward->{$field} = $value;
            }
        }

        $changed = $reward->isDirty();

        $reward->save();

        if ($changed) {
            event(new EconomyActionUpdated(
                actionId: $reward->action_id,
                profileId: $reward->action->balance_profile_id,
                changedFields: ['rewards'],
            ));
        }

        return $reward;
    }
}
