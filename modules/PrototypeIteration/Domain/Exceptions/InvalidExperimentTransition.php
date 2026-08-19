<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

use Modules\PrototypeIteration\Domain\Enums\ExperimentStatus;

/**
 * Raised when an experiment is asked to move somewhere its lifecycle does not
 * go.
 *
 * The set of legal moves lives on {@see ExperimentStatus}. The refusal worth
 * knowing about is planned → completed: an experiment's value is the result it
 * produced, and one that was never run has none, so a dropped question is
 * cancelled rather than completed.
 */
final class InvalidExperimentTransition extends IterationRuleViolation
{
    private function __construct(
        public readonly ExperimentStatus $from,
        public readonly ExperimentStatus $to,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function between(ExperimentStatus $from, ExperimentStatus $to): self
    {
        return new self($from, $to, __('A :from experiment cannot be moved to :to.', [
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
