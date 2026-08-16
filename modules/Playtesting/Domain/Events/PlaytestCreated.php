<?php

namespace Modules\Playtesting\Domain\Events;

/**
 * Dispatched when a designer plans a playtest against a version of a game.
 *
 * Carries the version as well as the game, because the interesting fact for a
 * consumer is almost never "this game is being tested" but "this iteration is
 * being tested" — that is what a progress tracker, a gamification rule or a
 * future analytics rollup actually keys off.
 */
final readonly class PlaytestCreated
{
    public function __construct(
        public string $playtestId,
        public string $gameId,
        public string $gameVersionId,
        public string $createdBy,
    ) {}
}
