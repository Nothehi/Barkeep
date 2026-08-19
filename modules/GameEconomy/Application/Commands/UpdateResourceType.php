<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\ResourceTypeData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Events\ResourceTypeUpdated;
use Modules\GameEconomy\Domain\Exceptions\EconomySlugIsTaken;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Domain\ValueObjects\EconomySlug;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Retune a resource.
 *
 * Every field is optional and only the ones the request mentioned are touched,
 * which is what makes the resource form and the inline editors on the resource
 * list the same endpoint. A partial update that overwrote what it did not
 * mention would make an inline "set the cap" control clear the description.
 *
 * Renaming re-derives the handle. That is a real consequence — a snapshot taken
 * before the rename matches on the old slug and will therefore report the
 * resource as removed and re-added — and it is still the right call: a handle
 * that no longer matches the name is worse, because it is the thing designers
 * read in the variable table.
 */
final class UpdateResourceType
{
    public function __construct(
        private readonly EconomyRepository $economy,
        private readonly BalanceWorkGuard $guard,
    ) {}

    public function handle(User $actor, ResourceType $resource, ResourceTypeData $data): ResourceType
    {
        $profile = $resource->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        if ($data->name !== null && $data->name !== $resource->name) {
            $slug = EconomySlug::fromName($data->name);

            if ($profile !== null && $this->economy->profileHasResourceSlug($profile, $slug, $resource->getKey())) {
                throw EconomySlugIsTaken::forResource($slug);
            }

            $resource->name = $data->name;
            $resource->slug = $slug->value;
        }

        foreach (['description', 'unit'] as $field) {
            if ($data->sent($field)) {
                $resource->{$field} = $field === 'description' ? $data->description : $data->unit;
            }
        }

        if ($data->category !== null) {
            $resource->category = $data->category;
        }

        foreach ([
            'is_tradeable' => $data->isTradeable,
            'is_accumulative' => $data->isAccumulative,
            'is_spendable' => $data->isSpendable,
            'is_convertible' => $data->isConvertible,
        ] as $field => $value) {
            if ($value !== null) {
                $resource->{$field} = $value;
            }
        }

        /*
         * The bounds are cleared when sent empty and left alone when absent.
         * Null means unbounded, which is a statement a designer makes on
         * purpose, so both edits have to be possible.
         */
        foreach ([
            'min_value' => $data->minValue,
            'max_value' => $data->maxValue,
            'starting_value' => $data->startingValue,
        ] as $field => $value) {
            if ($data->sent($field)) {
                $resource->{$field} = $value;
            }
        }

        if ($data->position !== null) {
            $resource->position = $data->position;
        }

        $changed = array_keys($resource->getDirty());

        $resource->save();

        if ($changed !== []) {
            event(new ResourceTypeUpdated(
                resourceId: $resource->getKey(),
                profileId: $resource->balance_profile_id,
                changedFields: $changed,
            ));
        }

        return $resource;
    }
}
