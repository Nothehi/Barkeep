<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

use Modules\PrototypeIteration\Domain\Enums\IterationStatus;

/**
 * Raised when an iteration is asked to move somewhere its lifecycle does not go.
 *
 * The set of legal moves lives on {@see IterationStatus}; this is what happens
 * when a caller asks for one that is not in it. The move it refuses most often
 * is completed → in progress, which is the module's historical integrity rule
 * meeting a client that expected an editable status field.
 */
final class InvalidIterationTransition extends IterationRuleViolation
{
    private function __construct(
        public readonly IterationStatus $from,
        public readonly IterationStatus $to,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function between(IterationStatus $from, IterationStatus $to): self
    {
        return new self($from, $to, __('A :from iteration cannot be moved to :to.', [
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
