<?php

namespace Modules\Playtesting\Domain\Enums;

/**
 * Where a playtest sits in its own life.
 *
 * A playtest is an *investigation*: somebody wrote down a question about a
 * version of a game and set out to answer it. This status tracks that
 * investigation, not any one evening around a table — the sessions have their
 * own lifecycle in {@see PlaytestSessionStatus}, and they move independently.
 * A playtest with four completed sessions is still in progress until the
 * designer says they have learned enough.
 *
 * The transitions below are the whole lifecycle. Encoding them here rather
 * than in a controller is what makes "a cancelled playtest cannot be resumed"
 * a property of the domain instead of an omission somebody has to remember.
 */
enum PlaytestStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * The status a playtest starts life in.
     */
    public static function default(): self
    {
        return self::Planned;
    }

    /**
     * The statuses this one may legally move to.
     *
     * Planned reaches Completed directly on purpose. A designer who planned
     * three sessions, ran none of them and decided the question was answered
     * anyway should not have to fake a session to close the investigation —
     * and a playtest with no sessions at all is refused by the command rather
     * than by this matrix, because that is a rule about evidence rather than
     * about the lifecycle.
     *
     * Both endings are terminal. Reopening a finished investigation would make
     * the record of what was concluded, and when, unreliable; the next
     * question gets its own playtest, which is also how a designer thinks
     * about it.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Planned => [self::InProgress, self::Completed, self::Cancelled],
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
     * Determine whether the playtest's plan may still be rewritten.
     *
     * The plan is the question being asked: the title, the objective, the
     * hypothesis, the version under test and when it was meant to happen.
     * Once the playtest is over, changing any of those would rewrite what the
     * evidence was gathered against, which is the one thing a playtest record
     * exists to preserve.
     */
    public function allowsModification(): bool
    {
        return $this === self::Planned || $this === self::InProgress;
    }

    /**
     * Determine whether the playtest may still gain sessions.
     */
    public function allowsSessions(): bool
    {
        return $this->allowsModification();
    }

    /**
     * Determine whether what was learned may still be written down.
     *
     * This is the exception to the freeze above, and the reason the two
     * questions are separate. Conclusions are written *after* the sessions
     * are over — often days after, once somebody has read back through the
     * observations — so a completed playtest has to stay open to that one
     * field while everything that was under test stays fixed.
     *
     * A cancelled playtest is closed to it too: an investigation that was
     * called off has nothing to conclude.
     */
    public function allowsAnalysis(): bool
    {
        return $this !== self::Cancelled;
    }

    /**
     * Determine whether the playtest has any life left in it.
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
     *
     * Lifecycle changes are explicit actions rather than a free choice from a
     * dropdown, so the wording of each one belongs beside the matrix that
     * allows it and the client renders whatever the server offers.
     */
    public function transitionLabelTo(self $target): string
    {
        return match ($target) {
            self::Planned => __('Return to planning'),
            self::InProgress => __('Start playtest'),
            self::Completed => __('Complete playtest'),
            self::Cancelled => __('Cancel playtest'),
        };
    }

    /**
     * The message shown when a change is refused because of this status.
     */
    public function deniedReason(): string
    {
        return match ($this) {
            self::Planned => __('This playtest is still being planned.'),
            self::InProgress => __('This playtest is under way.'),
            self::Completed => __('This playtest has been completed and its plan is read-only.'),
            self::Cancelled => __('This playtest was cancelled and is read-only.'),
        };
    }
}
