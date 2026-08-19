<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

use Modules\PrototypeIteration\Domain\Enums\PrototypeStatus;

/**
 * Raised when a prototype is asked to move somewhere its lifecycle does not go.
 *
 * The set of legal moves lives on {@see PrototypeStatus}; this is what happens
 * when a caller asks for one that is not in it — most often un-archiving, which
 * the lifecycle refuses on purpose.
 */
final class InvalidPrototypeTransition extends IterationRuleViolation
{
    private function __construct(
        public readonly PrototypeStatus $from,
        public readonly PrototypeStatus $to,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function between(PrototypeStatus $from, PrototypeStatus $to): self
    {
        return new self($from, $to, __('A :from prototype cannot be moved to :to.', [
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
