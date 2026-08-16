<?php

namespace Modules\Playtesting\Domain\Exceptions;

use Modules\Playtesting\Domain\Enums\PlaytestSessionStatus;

/**
 * Raised when a session is asked to move somewhere its lifecycle does not go.
 *
 * This is what a lost race looks like from the losing side. Two people press
 * "Start session" at the same moment and one of them arrives to find the
 * session already running; they get this rather than a second start that
 * quietly overwrites the first one's timestamp.
 */
final class InvalidSessionTransition extends PlaytestRuleViolation
{
    private function __construct(
        public readonly PlaytestSessionStatus $from,
        public readonly PlaytestSessionStatus $to,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function between(PlaytestSessionStatus $from, PlaytestSessionStatus $to): self
    {
        return new self($from, $to, __('A :from session cannot be moved to :to.', [
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
