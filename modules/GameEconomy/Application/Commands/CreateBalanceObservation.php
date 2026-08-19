<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\BalanceObservationData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Enums\ObservationSeverity;
use Modules\GameEconomy\Domain\Enums\ObservationSourceType;
use Modules\GameEconomy\Domain\Models\BalanceObservation;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\Identity\Domain\Models\User;

/**
 * Record what the studio noticed about the economy.
 *
 * The source reference is stored as whatever string was sent and is not resolved
 * against anything. That is the boundary being kept: looking a playtest id up
 * here would mean GameEconomy importing Playtesting, and once it can do that it
 * will end up holding a copy of the evidence rather than a pointer to it.
 *
 * This is the balance *interpretation* of evidence, not the evidence. "The green
 * player never bought a building" belongs to Playtesting; "buildings are priced
 * out of reach in the first four rounds" belongs here, and the two being
 * separate records is what lets somebody disagree with the second without
 * editing the first.
 */
final class CreateBalanceObservation
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $creator, BalanceProfile $profile, BalanceObservationData $data): BalanceObservation
    {
        $this->guard->ensureProfileAcceptsConfiguration($profile);

        $observation = new BalanceObservation;

        $observation->fill([
            'title' => $data->title ?? '',
            'observation' => $data->observation ?? '',
            'source_reference' => $data->sourceReference,
        ]);

        $observation->balance_profile_id = $profile->getKey();
        $observation->source_type = $data->sourceType ?? ObservationSourceType::default();
        $observation->severity = $data->severity ?? ObservationSeverity::default();
        $observation->created_by = $creator->id;

        $observation->save();

        $observation->setRelation('profile', $profile);
        $observation->setRelation('creator', $creator);

        return $observation;
    }
}
