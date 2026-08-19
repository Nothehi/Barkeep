<?php

namespace Modules\GameEconomy\Application\Commands;

use Modules\GameEconomy\Application\DTOs\BalanceObservationData;
use Modules\GameEconomy\Application\Services\BalanceWorkGuard;
use Modules\GameEconomy\Domain\Models\BalanceObservation;
use Modules\Identity\Domain\Models\User;

/**
 * Revise what the studio noticed, or how badly it reads.
 *
 * Severity is the field this exists for. An observation filed as medium after
 * one session and confirmed as critical after three should be able to say so
 * without a second row saying the same thing louder.
 */
final class UpdateBalanceObservation
{
    public function __construct(private readonly BalanceWorkGuard $guard) {}

    public function handle(User $actor, BalanceObservation $observation, BalanceObservationData $data): BalanceObservation
    {
        $profile = $observation->profile;

        if ($profile !== null) {
            $this->guard->ensureProfileAcceptsConfiguration($profile);
        }

        if ($data->title !== null) {
            $observation->title = $data->title;
        }

        if ($data->observation !== null) {
            $observation->observation = $data->observation;
        }

        if ($data->sourceType !== null) {
            $observation->source_type = $data->sourceType;
        }

        if ($data->sent('source_reference')) {
            $observation->source_reference = $data->sourceReference;
        }

        if ($data->severity !== null) {
            $observation->severity = $data->severity;
        }

        $observation->save();

        return $observation;
    }
}
