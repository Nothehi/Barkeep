<?php

namespace Modules\PrototypeIteration\Domain\Enums;

/**
 * Where an experiment sits in its own life.
 *
 * An experiment is a focused attempt to answer one design question, and it has
 * its own lifecycle because it does not move with the iteration around it. An
 * iteration in progress can hold a planned experiment, a running one and two
 * completed ones at the same time — which is the normal case, not an edge one.
 *
 * That independence is deliberate and is enforced rather than merely allowed:
 * completing an iteration does *not* complete its experiments. An experiment
 * that is still running when the cycle closes stayed unanswered, and silently
 * marking it complete would put a result into the record that nobody observed.
 */
enum ExperimentStatus: string
{
    case Planned = 'planned';
    case Running = 'running';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * The status an experiment starts life in.
     */
    public static function default(): self
    {
        return self::Planned;
    }

    /**
     * The statuses this one may legally move to.
     *
     * Planned does not reach completed. An experiment's value is its actual
     * result, and an experiment nobody ran has none — so a question that was
     * dropped gets cancelled, which says so honestly.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Planned => [self::Running, self::Cancelled],
            self::Running => [self::Completed, self::Cancelled],
            self::Completed => [],
            self::Cancelled => [],
        };
    }

    /**
     * Determine whether this status may move to the given one.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->transitions(), strict: true);
    }

    /**
     * Determine whether the experiment's design may still be rewritten.
     *
     * The design is the question, the hypothesis, the method and the expected
     * result — everything written down *before* the experiment ran. Once it is
     * over, editing any of those is how a prediction becomes retroactively
     * correct, so the window closes when the experiment does.
     */
    public function allowsModification(): bool
    {
        return $this === self::Planned || $this === self::Running;
    }

    /**
     * Determine whether the experiment is over, however it ended.
     */
    public function isTerminal(): bool
    {
        return $this->transitions() === [];
    }

    /**
     * A human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Planned => __('Planned'),
            self::Running => __('Running'),
            self::Completed => __('Completed'),
            self::Cancelled => __('Cancelled'),
        };
    }

    /**
     * The verb offered for moving from this status to the given one.
     */
    public function transitionLabelTo(self $target): string
    {
        return match ($target) {
            self::Planned => __('Return to planning'),
            self::Running => __('Start experiment'),
            self::Completed => __('Complete experiment'),
            self::Cancelled => __('Cancel experiment'),
        };
    }

    /**
     * The message shown when a change is refused because of this status.
     */
    public function deniedReason(): string
    {
        return match ($this) {
            self::Planned => __('This experiment has not started yet.'),
            self::Running => __('This experiment is running.'),
            self::Completed => __('This experiment has been completed and its result is read-only.'),
            self::Cancelled => __('This experiment was cancelled and is read-only.'),
        };
    }
}
