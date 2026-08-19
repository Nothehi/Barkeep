<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\ActionEffectData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Enums\ActionEffectType;
use Modules\GameEconomy\Domain\Events\EconomyActionUpdated;
use Modules\GameEconomy\Domain\Models\ActionEffect;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\Identity\Domain\Models\User;

/**
 * Record something an action does beyond moving resources.
 *
 * There is no resource to resolve and no ownership to prove, which is what makes
 * this the shortest write in the module — the target is free text on purpose,
 * because the things an effect acts on are not all rows and forcing them to be
 * would be the schema deciding what a game contains.
 */
final class AddActionEffect
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $actor, EconomyAction $action, ActionEffectData $data): ActionEffect
    {
        $profile = $action->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        $effect = new ActionEffect;

        $effect->fill([
            'target' => $data->target ?? '',
            'description' => $data->description,
        ]);

        $effect->action_id = $action->getKey();
        $effect->effect_type = $data->effectType ?? ActionEffectType::default();
        $effect->value = $data->value;

        $effect->save();

        $effect->setRelation('action', $action);

        event(new EconomyActionUpdated(
            actionId: $action->getKey(),
            profileId: $action->balance_profile_id,
            changedFields: ['effects'],
        ));

        return $effect;
    }
}
