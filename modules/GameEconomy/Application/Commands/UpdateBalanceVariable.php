<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\BalanceVariableData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Application\Services\EconomyCatalogue;
use Modules\GameEconomy\Domain\Events\BalanceVariableUpdated;
use Modules\GameEconomy\Domain\Exceptions\EconomySlugIsTaken;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Domain\ValueObjects\EconomySlug;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Change a tunable number.
 *
 * The command the variable table's inline editing runs against, which is why
 * every field is optional: a cell that only sends `value` must not clear the
 * unit, the range or the category around it.
 *
 * It changes the base value and only the base value. A scenario that states a
 * different number is untouched — the override lives in its own table, so there
 * is no path from here that could reach it.
 */
final class UpdateBalanceVariable
{
    public function __construct(
        private readonly EconomyCatalogue $catalogue,
        private readonly EconomyRepository $economy,
        private readonly BalanceWorkGuard $guard,
    ) {}

    public function handle(User $actor, BalanceVariable $variable, BalanceVariableData $data): BalanceVariable
    {
        $profile = $variable->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        if ($data->name !== null && $data->name !== $variable->name) {
            $slug = EconomySlug::fromName($data->name);

            if ($profile !== null && $this->economy->profileHasVariableSlug($profile, $slug, $variable->getKey())) {
                throw EconomySlugIsTaken::forVariable($slug);
            }

            $variable->name = $data->name;
            $variable->slug = $slug->value;
        }

        if ($data->value !== null) {
            $variable->value = $data->value;
        }

        foreach (['description', 'unit'] as $field) {
            if ($data->sent($field)) {
                $variable->{$field} = $field === 'description' ? $data->description : $data->unit;
            }
        }

        foreach ([
            'min_value' => $data->minValue,
            'max_value' => $data->maxValue,
            'step' => $data->step,
        ] as $field => $value) {
            if ($data->sent($field)) {
                $variable->{$field} = $value;
            }
        }

        if ($data->category !== null) {
            $variable->category = $data->category;
        }

        /*
         * Either reference may be cleared by sending it empty, which is how a
         * designer detaches a variable from an action they are about to rename
         * away. A resolved id is proved to belong to this profile first.
         */
        if ($profile !== null && $data->sent('resource_type_id')) {
            $variable->resource_type_id = $data->resourceTypeId === null
                ? null
                : $this->catalogue->resourceOf($profile, $data->resourceTypeId)->getKey();
        }

        if ($profile !== null && $data->sent('action_id')) {
            $variable->action_id = $data->actionId === null
                ? null
                : $this->catalogue->actionOf($profile, $data->actionId)->getKey();
        }

        $changed = array_keys($variable->getDirty());

        $variable->save();

        if ($changed !== []) {
            event(new BalanceVariableUpdated(
                variableId: $variable->getKey(),
                profileId: $variable->balance_profile_id,
                changedFields: $changed,
            ));
        }

        return $variable;
    }
}
