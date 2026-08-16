<?php

namespace Modules\Playtesting\Domain\Exceptions;

use Modules\Playtesting\Domain\Enums\PlaytestStatus;

/**
 * Raised when a playtest is asked to move somewhere its lifecycle does not go.
 *
 * The set of legal moves lives on {@see PlaytestStatus}; this is what happens
 * when a caller asks for one that is not in it.
 */
final class InvalidPlaytestTransition extends PlaytestRuleViolation
{
    private function __construct(
        public readonly PlaytestStatus $from,
        public readonly PlaytestStatus $to,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function between(PlaytestStatus $from, PlaytestStatus $to): self
    {
        return new self($from, $to, __('A :from playtest cannot be moved to :to.', [
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
