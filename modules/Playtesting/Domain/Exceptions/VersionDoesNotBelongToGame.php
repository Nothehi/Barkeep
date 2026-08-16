<?php

namespace Modules\Playtesting\Domain\Exceptions;

/**
 * Raised when a playtest names a version that belongs to a different game.
 *
 * The central invariant of the module. A playtest says "we tested *this*
 * version of *this* game", and if those two can disagree the record is not
 * evidence of anything — every conclusion drawn from it is attached to a
 * design nobody actually played.
 *
 * The pairing arrives from a client, which is why this exists at all: the game
 * comes from a resolved route binding and the version from the request body,
 * so the two have to be proved to match rather than assumed to.
 */
final class VersionDoesNotBelongToGame extends PlaytestRuleViolation
{
    private function __construct(
        public readonly string $gameId,
        public readonly string $versionId,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function forPair(string $gameId, string $versionId): self
    {
        return new self($gameId, $versionId, __('That version belongs to a different game.'));
    }

    /**
     * Reported against the submitted field so the form can show it in place.
     */
    public function field(): string
    {
        return 'game_version_id';
    }
}
