<?php

namespace Modules\DesignFramework\Domain\Exceptions;

use Modules\DesignFramework\Domain\Enums\GameFrameworkStatus;

/**
 * Raised when a game's adoption is asked to make a move its lifecycle does not
 * allow.
 *
 * In practice this is almost always an attempt to reopen a completed adoption.
 * The set of legal moves lives on {@see GameFrameworkStatus}.
 */
final class InvalidGameFrameworkTransition extends FrameworkRuleViolation
{
    private function __construct(
        public readonly GameFrameworkStatus $from,
        public readonly GameFrameworkStatus $to,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function between(GameFrameworkStatus $from, GameFrameworkStatus $to): self
    {
        return new self($from, $to, __('A :from framework cannot be moved to :to.', [
            'from' => mb_strtolower($from->label()),
            'to' => mb_strtolower($to->label()),
        ]));
    }

    public function status(): int
    {
        return 409;
    }
}
