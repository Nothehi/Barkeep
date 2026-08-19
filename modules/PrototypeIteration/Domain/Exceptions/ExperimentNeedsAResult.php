<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

/**
 * Raised when an experiment is completed without recording what happened.
 *
 * An experiment's whole value is its actual result. Completing one without a
 * result would put a settled-looking entry into the iteration timeline that
 * answers nothing — and the timeline is read as an account of what the studio
 * found out.
 *
 * The conclusion is deliberately not required alongside it. What happened is a
 * fact the person who ran the session has; what it means is a judgement that
 * often arrives days later, and demanding it at the same moment would produce
 * conclusions written to fill a field.
 */
final class ExperimentNeedsAResult extends IterationRuleViolation
{
    private function __construct(public readonly string $experimentId, string $message)
    {
        parent::__construct($message);
    }

    public static function forExperiment(string $experimentId): self
    {
        return new self($experimentId, __('Say what actually happened before completing the experiment.'));
    }

    /**
     * Reported against the submitted field so the form can show it in place.
     */
    public function field(): string
    {
        return 'actual_result';
    }
}
