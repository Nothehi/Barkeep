<?php

namespace Modules\GameEconomy\Domain\Exceptions;

/**
 * Raised when a design version id names a state of somebody else's game.
 *
 * Attributed to the field so it surfaces beside the version picker rather than
 * in a toast. It should be close to unreachable in practice — versions are
 * resolved *through* the game rather than compared against it — and exists so
 * that the one path where an id arrives in a request body fails in a way
 * somebody can act on.
 */
final class VersionDoesNotBelongToGame extends EconomyRuleViolation
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

    public function field(): string
    {
        return 'game_version_id';
    }
}
