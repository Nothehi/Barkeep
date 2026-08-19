<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\ResourceTypeData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Enums\ResourceCategory;
use Modules\GameEconomy\Domain\Events\ResourceTypeCreated;
use Modules\GameEconomy\Domain\Exceptions\EconomySlugIsTaken;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Domain\ValueObjects\EconomySlug;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Declare something players hold, gain and spend.
 *
 * The handle is derived from the name rather than typed, so a designer naming a
 * resource "Action Points" does not also have to invent `action_points` — and
 * two people naming the same thing arrive at the same handle. The clash is
 * reported against `name`, because that is the field they actually filled in.
 *
 * The position defaults to the end of the list. Economies have a reading order
 * and new resources belong at the bottom of it until somebody moves them, which
 * is less surprising than a new resource appearing in the middle.
 */
final class CreateResourceType
{
    public function __construct(
        private readonly EconomyRepository $economy,
        private readonly BalanceWorkGuard $guard,
    ) {}

    public function handle(User $actor, BalanceProfile $profile, ResourceTypeData $data): ResourceType
    {
        $this->guard->ensureProfileAcceptsConfiguration($profile);

        $name = $data->name ?? '';
        $slug = EconomySlug::fromName($name);

        if ($this->economy->profileHasResourceSlug($profile, $slug)) {
            throw EconomySlugIsTaken::forResource($slug);
        }

        $resource = new ResourceType;

        $resource->fill([
            'name' => $name,
            'description' => $data->description,
            'unit' => $data->unit,
        ]);

        $resource->balance_profile_id = $profile->getKey();
        $resource->slug = $slug->value;
        $resource->category = $data->category ?? ResourceCategory::default();
        $resource->is_tradeable = $data->isTradeable ?? true;
        $resource->is_accumulative = $data->isAccumulative ?? true;
        $resource->is_spendable = $data->isSpendable ?? true;
        $resource->is_convertible = $data->isConvertible ?? false;
        $resource->min_value = $data->minValue;
        $resource->max_value = $data->maxValue;
        $resource->starting_value = $data->startingValue;
        $resource->position = $data->position ?? $this->economy->resourcesOf($profile)->count();

        $resource->save();

        $resource->setRelation('profile', $profile);

        event(new ResourceTypeCreated(
            resourceId: $resource->id,
            profileId: $profile->getKey(),
            slug: $slug->value,
            category: $resource->category,
        ));

        return $resource;
    }
}
