<?php

namespace Modules\DesignFramework\Domain\Exceptions;

/**
 * Raised when a game that is already following a methodology is asked to adopt
 * another.
 *
 * One framework per game, enforced by a unique index on `game_id` as well as
 * here. Supporting several at once is a real product question — whose progress
 * is *the* progress? — and answering it later is cheaper than guessing now.
 *
 * Note what this is not: it is not migration. Moving a game from v1 to v2 is a
 * deliberate operation with its own rules about what happens to evaluations
 * already recorded, and the module does not implement it yet. Silently
 * reassigning a game's framework here would be that operation, done badly.
 */
final class GameAlreadyFollowsAFramework extends FrameworkRuleViolation
{
    private function __construct(public readonly string $gameId, string $message)
    {
        parent::__construct($message);
    }

    public static function forGame(string $gameId): self
    {
        return new self($gameId, __('This game is already following a design framework.'));
    }

    public function status(): int
    {
        return 409;
    }

    /**
     * Reported against the submitted field so the form can show it in place.
     */
    public function field(): string
    {
        return 'framework_version_id';
    }
}
