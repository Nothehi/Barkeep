<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\ActionEffectData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Events\EconomyActionUpdated;
use Modules\GameEconomy\Domain\Models\ActionEffect;
use Modules\Identity\Domain\Models\User;

/**
 * Change what an action does beyond moving resources.
 */
final class UpdateActionEffect
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $actor, ActionEffect $effect, ActionEffectData $data): ActionEffect
    {
        $profile = $effect->action?->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        if ($data->effectType !== null) {
            $effect->effect_type = $data->effectType;
        }

        if ($data->target !== null) {
            $effect->target = $data->target;
        }

        if ($data->sent('value')) {
            $effect->value = $data->value;
        }

        if ($data->sent('description')) {
            $effect->description = $data->description;
        }

        $changed = $effect->isDirty();

        $effect->save();

        if ($changed) {
            event(new EconomyActionUpdated(
                actionId: $effect->action_id,
                profileId: $effect->action->balance_profile_id,
                changedFields: ['effects'],
            ));
        }

        return $effect;
    }
}
