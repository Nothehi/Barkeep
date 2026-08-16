<?php

namespace Modules\Playtesting\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Infrastructure\Persistence\Repositories\PlaytestRepository;

/**
 * A playtest's sittings, earliest first, with what each one produced.
 *
 * Ordered forwards rather than backwards, because sessions are read as a
 * sequence: "by the third group they had stopped asking about scoring" only
 * makes sense in order.
 *
 * @see PlaytestRepository::sessionsOf()
 */
final class GetSessions
{
    public function __construct(private readonly PlaytestRepository $playtests) {}

    /**
     * @return Collection<int, PlaytestSession>
     */
    public function handle(Playtest $playtest): Collection
    {
        return $this->playtests->sessionsOf($playtest);
    }
}
