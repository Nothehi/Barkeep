<?php

namespace Modules\Playtesting\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Infrastructure\Persistence\Repositories\PlaytestRepository;

/**
 * The people at a session, in the order they were added.
 *
 * Insertion order rather than alphabetical: it is roughly seating order, and a
 * facilitator scanning the list during a session is looking for the person
 * they added third, not the one whose name starts with C.
 *
 * @see PlaytestRepository::participantsOf()
 */
final class GetParticipants
{
    public function __construct(private readonly PlaytestRepository $playtests) {}

    /**
     * @return Collection<int, PlaytestParticipant>
     */
    public function handle(PlaytestSession $session): Collection
    {
        return $this->playtests->participantsOf($session);
    }
}
