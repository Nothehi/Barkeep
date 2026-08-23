<?php

namespace Modules\GameRules\Domain\Exceptions;

/**
 * Raised when a design version is named against a game it does not belong to.
 *
 * The module's foundational pairing check. It should be unreachable through the
 * screens, because the router resolves the version *through* the game and a
 * mismatched one 404s before a handler runs — but the commands prove it again,
 * so a caller arriving another way cannot file a rule set against somebody
 * else's design state.
 */
final class VersionDoesNotBelongToGame extends RuleSystemViolation
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
