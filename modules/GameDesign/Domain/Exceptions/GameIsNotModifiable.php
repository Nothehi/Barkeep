<?php

namespace Modules\GameDesign\Domain\Exceptions;

use Modules\GameDesign\Domain\Enums\GameStatus;

/**
 * Raised when something tries to change a game that has been put away.
 *
 * This is the read-only guarantee for archived games, enforced in the
 * application layer rather than only in the policy. The policy stops the
 * request; this stops the *operation*, including any future caller that
 * reaches a command without going through HTTP.
 */
final class GameIsNotModifiable extends GameRuleViolation
{
    /**
     * @param  GameStatus|null  $status  the game's own status, when that is what refused
     */
    private function __construct(public readonly ?GameStatus $status, string $message)
    {
        parent::__construct($message);
    }

    public static function forStatus(GameStatus $status): self
    {
        return new self($status, $status->deniedReason());
    }

    /**
     * Raised when the game is fine but the workspace around it is not.
     *
     * A game inside an archived or suspended workspace is as frozen as an
     * archived game, and for the same reason: the boundary it lives in has
     * stopped accepting changes. The status is absent because the game's own
     * is not what refused.
     */
    public static function becauseWorkspaceIsClosed(string $reason): self
    {
        return new self(null, $reason);
    }

    public function status(): int
    {
        return 409;
    }
}
