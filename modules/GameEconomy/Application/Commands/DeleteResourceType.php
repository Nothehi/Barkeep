<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Events\ResourceTypeDeleted;
use Modules\GameEconomy\Domain\Exceptions\ResourceIsInUse;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Remove a resource from a configuration.
 *
 * Refused outright while anything is priced, paid out, moved or measured in it.
 * The database would refuse too — costs and rewards reference resources with
 * `restrictOnDelete` — but a foreign key violation reaches a designer as a 500,
 * and what they need to know is not "constraint failed" but "eleven actions are
 * priced in wood".
 *
 * The check is deliberately wider than the database's. Flows and variables
 * cascade or null out at the schema level, so the database would let those go
 * silently — and a resource disappearing from under four flows and six variables
 * is exactly the change nobody notices until a playtest goes wrong.
 *
 * Deleting rather than archiving, unlike almost everything else in the platform.
 * A resource with nothing pointing at it is a mistake somebody made two minutes
 * ago, not a piece of history: it has no evidence attached and nothing has been
 * reasoned about it, which is precisely why the usage check is what makes the
 * deletion safe.
 */
final class DeleteResourceType
{
    public function __construct(
        private readonly EconomyRepository $economy,
        private readonly BalanceWorkGuard $guard,
    ) {}

    public function handle(User $actor, ResourceType $resource): void
    {
        $profile = $resource->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        $usage = $this->economy->countUsesOfResource($resource);

        if ($usage > 0) {
            throw ResourceIsInUse::forResource($resource->getKey(), $usage);
        }

        $resourceId = $resource->getKey();
        $profileId = $resource->balance_profile_id;
        $slug = $resource->slug;

        $resource->delete();

        event(new ResourceTypeDeleted(
            resourceId: $resourceId,
            profileId: $profileId,
            slug: $slug,
        ));
    }
}
