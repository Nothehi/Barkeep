<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

/**
 * Raised when an iteration is attached to a playtest from another game.
 *
 * The boundary check on the Playtesting side. An iteration's evidence has to be
 * evidence about the same project, and a playtest id arrives in a request body
 * — so it is resolved *through* the iteration's own game, using Playtesting's
 * published query, and a playtest from elsewhere simply does not resolve.
 *
 * Worded without distinguishing "does not exist" from "belongs to another
 * studio", for the reason every lookup failure in the platform is worded that
 * way: telling them apart would let an id be used to discover what other
 * studios are working on.
 */
final class PlaytestDoesNotBelongToGame extends IterationRuleViolation
{
    private function __construct(
        public readonly string $gameId,
        public readonly string $playtestId,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function forPair(string $gameId, string $playtestId): self
    {
        return new self($gameId, $playtestId, __('That is not a playtest of this game.'));
    }

    /**
     * Reported against the submitted field so the form can show it in place.
     */
    public function field(): string
    {
        return 'playtest_id';
    }
}
