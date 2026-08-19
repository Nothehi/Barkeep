<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

/**
 * Raised when a prototype or iteration names a game version from another game.
 *
 * Half of the module's central invariant. A prototype says "this implements
 * *this* design state of *this* game"; an iteration says the same about the
 * design it was working against. If those can disagree, the record is not
 * history — it describes work on a design nobody was doing.
 *
 * The pairing arrives from a client, which is why this exists at all: the game
 * comes from a resolved route binding and the version from the request body, so
 * the two have to be proved to match rather than assumed to.
 */
final class VersionDoesNotBelongToGame extends IterationRuleViolation
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
