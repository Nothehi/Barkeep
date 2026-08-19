<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\EconomyActionData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Events\EconomyActionUpdated;
use Modules\GameEconomy\Domain\Exceptions\EconomySlugIsTaken;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\ValueObjects\EconomySlug;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Rename an action or change what it is for.
 *
 * What the action *does* is not editable from here — costs, rewards and effects
 * have their own commands, because each is a separate row and editing one must
 * not be able to disturb another.
 */
final class UpdateEconomyAction
{
    public function __construct(
        private readonly EconomyRepository $economy,
        private readonly BalanceWorkGuard $guard,
    ) {}

    public function handle(User $actor, EconomyAction $action, EconomyActionData $data): EconomyAction
    {
        $profile = $action->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        if ($data->name !== null && $data->name !== $action->name) {
            $slug = EconomySlug::fromName($data->name);

            if ($profile !== null && $this->economy->profileHasActionSlug($profile, $slug, $action->getKey())) {
                throw EconomySlugIsTaken::forAction($slug);
            }

            $action->name = $data->name;
            $action->slug = $slug->value;
        }

        if ($data->sent('description')) {
            $action->description = $data->description;
        }

        if ($data->position !== null) {
            $action->position = $data->position;
        }

        $changed = array_keys($action->getDirty());

        $action->save();

        if ($changed !== []) {
            event(new EconomyActionUpdated(
                actionId: $action->getKey(),
                profileId: $action->balance_profile_id,
                changedFields: $changed,
            ));
        }

        return $action;
    }
}
