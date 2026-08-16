<?php

namespace Modules\GameDesign\Domain\Exceptions;

use Modules\GameDesign\Domain\Enums\GameStatus;

/**
 * Raised when a game is asked to move somewhere its lifecycle does not go.
 *
 * The set of legal moves lives on {@see GameStatus}; this is what happens
 * when a caller asks for one that is not in it.
 */
final class InvalidStatusTransition extends GameRuleViolation
{
    private function __construct(
        public readonly GameStatus $from,
        public readonly GameStatus $to,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function between(GameStatus $from, GameStatus $to): self
    {
        return new self($from, $to, __('A :from game cannot be moved to :to.', [
            'from' => mb_strtolower($from->label()),
            'to' => mb_strtolower($to->label()),
        ]));
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
        return 'status';
    }
}
