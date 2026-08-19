<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

use Modules\PrototypeIteration\Domain\Enums\ExperimentStatus;

/**
 * Raised when a finished experiment's design is edited.
 *
 * The design is the half written down before it ran: the question, the
 * hypothesis, the method and the expected result. Editing those after the result
 * is known is how a prediction becomes retroactively correct — usually
 * honestly, by somebody tidying up — and it is the failure this refusal exists
 * to prevent.
 */
final class ExperimentIsNotModifiable extends IterationRuleViolation
{
    private function __construct(public readonly ExperimentStatus $status, string $message)
    {
        parent::__construct($message);
    }

    public static function forStatus(ExperimentStatus $status): self
    {
        return new self($status, $status->deniedReason());
    }

    /**
     * Raised when the iteration around the experiment has closed.
     *
     * Reported as an experiment problem even though the iteration is what
     * refused, because the caller was acting on an experiment and that is the
     * object they are looking at. The message comes from the iteration, so they
     * are still told the real reason.
     */
    public static function becauseIterationIsClosed(string $reason): self
    {
        return new self(ExperimentStatus::Planned, $reason);
    }

    public function status(): int
    {
        return 409;
    }
}
