<?php

namespace Modules\Playtesting\Application\Queries;

use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Infrastructure\Persistence\Repositories\PlaytestRepository;

/**
 * One of a playtest's sittings, by id.
 *
 * Scoped through the playtest, which was itself scoped through the game and
 * the workspace. A session id belonging to another playtest returns null, so
 * the ownership chain holds by construction rather than by each caller
 * remembering to check it.
 */
final class GetSession
{
    public function __construct(private readonly PlaytestRepository $playtests) {}

    public function handle(Playtest $playtest, string $sessionId): ?PlaytestSession
    {
        return $this->playtests->findSessionForPlaytest($playtest, $sessionId);
    }
}
