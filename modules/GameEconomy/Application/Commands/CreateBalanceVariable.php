<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\BalanceVariableData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Application\Services\EconomyCatalogue;
use Modules\GameEconomy\Domain\Enums\BalanceVariableCategory;
use Modules\GameEconomy\Domain\Events\BalanceVariableCreated;
use Modules\GameEconomy\Domain\Exceptions\EconomySlugIsTaken;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Domain\ValueObjects\EconomySlug;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Expose a number for tuning.
 *
 * Both optional references are proved to belong to this profile before anything
 * is written — the same invariant a cost's resource is held to, asked from the
 * other side. A variable pointing at somebody else's action would be a number
 * about nothing, and the analysis reading it would be reporting on a game that
 * does not exist.
 *
 * The value is not checked against the range beside it. That is section 31: a
 * designer setting a range around a value they are about to change would be
 * blocked by a save that refused, so the analysis reports it instead.
 */
final class CreateBalanceVariable
{
    public function __construct(
        private readonly EconomyCatalogue $catalogue,
        private readonly EconomyRepository $economy,
        private readonly BalanceWorkGuard $guard,
    ) {}

    public function handle(User $actor, BalanceProfile $profile, BalanceVariableData $data): BalanceVariable
    {
        $this->guard->ensureProfileAcceptsConfiguration($profile);

        $name = $data->name ?? '';
        $slug = EconomySlug::fromName($name);

        if ($this->economy->profileHasVariableSlug($profile, $slug)) {
            throw EconomySlugIsTaken::forVariable($slug);
        }

        $variable = new BalanceVariable;

        $variable->fill([
            'name' => $name,
            'description' => $data->description,
            'unit' => $data->unit,
        ]);

        $variable->balance_profile_id = $profile->getKey();
        $variable->slug = $slug->value;
        $variable->value = $data->value ?? Quantity::zero();
        $variable->min_value = $data->minValue;
        $variable->max_value = $data->maxValue;
        $variable->step = $data->step;
        $variable->category = $data->category ?? BalanceVariableCategory::default();

        if ($data->resourceTypeId !== null) {
            $variable->resource_type_id = $this->catalogue->resourceOf($profile, $data->resourceTypeId)->getKey();
        }

        if ($data->actionId !== null) {
            $variable->action_id = $this->catalogue->actionOf($profile, $data->actionId)->getKey();
        }

        $variable->save();

        $variable->setRelation('profile', $profile);

        event(new BalanceVariableCreated(
            variableId: $variable->id,
            profileId: $profile->getKey(),
            slug: $slug->value,
            category: $variable->category,
        ));

        return $variable;
    }
}
