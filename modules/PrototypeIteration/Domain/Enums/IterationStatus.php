<?php

namespace Modules\PrototypeIteration\Domain\Enums;

/**
 * Where an iteration sits in its own life.
 *
 * An iteration is a *design cycle*: somebody wrote down what they wanted to
 * change and why, changed it, tested it, and decided what happens next. This
 * status tracks that cycle, and it is deliberately narrower than the playtests
 * inside it — a playtest can finish while the iteration around it is still
 * being argued about, which is usually exactly what is happening.
 *
 * The transitions below are the whole lifecycle. The refusal that matters most
 * is completed → in progress: an iteration's outcome is the sentence the next
 * iteration is built on, and reopening one would make the design history a
 * record of what somebody currently believes rather than of what they decided
 * at the time. A later change of mind is a new iteration, or an explicit
 * follow-up decision inside one.
 */
enum IterationStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * The status an iteration starts life in.
     */
    public static function default(): self
    {
        return self::Planned;
    }

    /**
     * The statuses this one may legally move to.
     *
     * Planned does not reach completed directly, which is the one place this
     * matrix is stricter than the playtest one. An iteration that completed
     * without ever starting would carry an outcome nobody gathered evidence
     * for; the honest move for a cycle that never happened is to cancel it.
     *
     * Both endings are terminal, for the reason given above the enum.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Planned => [self::InProgress, self::Cancelled],
            self::InProgress => [self::Completed, self::Cancelled],
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
     * Determine whether the iteration's plan may still be rewritten.
     *
     * The plan is what the cycle set out to do: the title, the objective, the
     * hypothesis and which prototype version it is about. Once the iteration
     * is over, changing any of those would rewrite what the changes and the
     * decisions were made against — which is the one thing the design history
     * exists to preserve.
     */
    public function allowsModification(): bool
    {
        return $this === self::Planned || $this === self::InProgress;
    }

    /**
     * Determine whether the iteration may still gain design work.
     *
     * Design work is everything the cycle produces: changes, experiments,
     * decisions and playtest attachments. It is the same window as the plan
     * being editable, and that is not a coincidence — a change recorded
     * against a finished iteration is a change nobody can date.
     */
    public function allowsWork(): bool
    {
        return $this->allowsModification();
    }

    /**
     * Determine whether the iteration is over, however it ended.
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
            self::InProgress => __('In progress'),
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
            self::InProgress => __('Start iteration'),
            self::Completed => __('Complete iteration'),
            self::Cancelled => __('Cancel iteration'),
        };
    }

    /**
     * The message shown when a change is refused because of this status.
     */
    public function deniedReason(): string
    {
        return match ($this) {
            self::Planned => __('This iteration has not started yet.'),
            self::InProgress => __('This iteration is under way.'),
            self::Completed => __('This iteration has been completed and is part of the design history.'),
            self::Cancelled => __('This iteration was cancelled and is read-only.'),
        };
    }
}
