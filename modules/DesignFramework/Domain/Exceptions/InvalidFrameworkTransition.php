<?php

namespace Modules\DesignFramework\Domain\Exceptions;

use Modules\DesignFramework\Domain\Enums\FrameworkStatus;

/**
 * Raised when a framework or version is asked to make a move its lifecycle
 * does not allow.
 *
 * The set of legal moves lives on {@see FrameworkStatus}, and the commands check
 * it against the status they read under a row lock rather than against whatever
 * the caller was looking at. Two people pressing "Publish" and "Archive" at the
 * same moment therefore produce one winner and one honest refusal, instead of a
 * last-write-wins result where the losing action reports success.
 */
final class InvalidFrameworkTransition extends FrameworkRuleViolation
{
    private function __construct(
        public readonly FrameworkStatus $from,
        public readonly FrameworkStatus $to,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function between(FrameworkStatus $from, FrameworkStatus $to): self
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
