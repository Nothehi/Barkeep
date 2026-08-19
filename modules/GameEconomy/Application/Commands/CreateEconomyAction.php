<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\EconomyActionData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Events\EconomyActionCreated;
use Modules\GameEconomy\Domain\Exceptions\EconomySlugIsTaken;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Domain\ValueObjects\EconomySlug;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Declare something that moves the economy.
 *
 * The action is created empty — no costs, no rewards, no effects — and that is
 * how designers work: "we need a Build action" comes before anybody has decided
 * what it takes. A create form that demanded a resource list would also demand
 * that the resources already existed, which is the wrong way round for a new
 * configuration.
 *
 * The analysis will report the empty action as costing nothing and doing
 * nothing, which is true and is exactly the reminder somebody needs.
 */
final class CreateEconomyAction
{
    public function __construct(
        private readonly EconomyRepository $economy,
        private readonly BalanceWorkGuard $guard,
    ) {}

    public function handle(User $actor, BalanceProfile $profile, EconomyActionData $data): EconomyAction
    {
        $this->guard->ensureProfileAcceptsConfiguration($profile);

        $name = $data->name ?? '';
        $slug = EconomySlug::fromName($name);

        if ($this->economy->profileHasActionSlug($profile, $slug)) {
            throw EconomySlugIsTaken::forAction($slug);
        }

        $action = new EconomyAction;

        $action->fill([
            'name' => $name,
            'description' => $data->description,
        ]);

        $action->balance_profile_id = $profile->getKey();
        $action->slug = $slug->value;
        $action->position = $data->position ?? $this->economy->actionsOf($profile)->count();

        $action->save();

        $action->setRelation('profile', $profile);

        event(new EconomyActionCreated(
            actionId: $action->id,
            profileId: $profile->getKey(),
            slug: $slug->value,
        ));

        return $action;
    }
}
