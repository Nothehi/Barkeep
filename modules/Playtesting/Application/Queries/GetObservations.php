<?php

namespace Modules\Playtesting\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Infrastructure\Persistence\Repositories\PlaytestRepository;

/**
 * What was noticed at a session, in the order it was noticed.
 *
 * Sorted by the moment of observation where there is one and by when it was
 * written down otherwise, so that notes typed up after the session land at the
 * end of the account rather than jumping to the front on a null.
 *
 * @see PlaytestRepository::observationsOf()
 */
final class GetObservations
{
    public function __construct(private readonly PlaytestRepository $playtests) {}

    /**
     * @return Collection<int, PlaytestObservation>
     */
    public function handle(PlaytestSession $session): Collection
    {
        return $this->playtests->observationsOf($session);
    }
}
