<?php

namespace Modules\Playtesting\Domain\Enums;

/**
 * Where one sitting of a playtest sits in its own life.
 *
 * A session is an event that happens to people in a room: it is scheduled, it
 * starts, it ends. That is a different thing from the investigation it belongs
 * to — see {@see PlaytestStatus} — and the two are tracked separately because
 * a playtest routinely outlives any one of its sessions.
 *
 * Unlike the playtest above, a session may not be completed straight from
 * planned. "Completed" asserts that the game was actually played, and the
 * timestamps that make a session useful as evidence — when it started, how
 * long it ran — only exist because somebody started it.
 */
enum PlaytestSessionStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * The status a session starts life in.
     */
    public static function default(): self
    {
        return self::Planned;
    }

    /**
     * The statuses this one may legally move to.
     *
     * A session that was called off before anybody sat down and one that was
     * abandoned halfway both end as cancelled; the timestamps say which
     * happened. Completed is terminal, so a finished session cannot later be
     * reclassified as cancelled and quietly removed from the evidence.
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
     * Determine whether the session's own details may still be changed.
     */
    public function allowsModification(): bool
    {
        return $this === self::Planned || $this === self::InProgress;
    }

    /**
     * Determine whether the session may still gain evidence.
     *
     * Participants, observations and feedback all hang off this answer, and
     * the answer is the same for all three: while the session is planned or
     * running, yes; once it is over, no.
     *
     * Completing a session is therefore the last thing done to it, and that
     * is deliberate. Evidence collected at a table is worth what it is worth
     * because it was recorded at the table; a record that stays open to
     * additions afterwards is a record nobody can date.
     */
    public function allowsEvidence(): bool
    {
        return $this->allowsModification();
    }

    /**
     * Determine whether the session has any life left in it.
     */
    public function isTerminal(): bool
    {
        return $this->transitions() === [];
    }

    /**
     * Determine whether the session actually happened.
     *
     * Used wherever sessions are counted as evidence rather than as plans —
     * the summary counts these, and only these, towards how much a playtest
     * has been exercised.
     */
    public function isConcluded(): bool
    {
        return $this === self::Completed;
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
            self::Planned => __('Return to planned'),
            self::InProgress => __('Start session'),
            self::Completed => __('End session'),
            self::Cancelled => __('Cancel session'),
        };
    }

    /**
     * The message shown when a change is refused because of this status.
     */
    public function deniedReason(): string
    {
        return match ($this) {
            self::Planned => __('This session has not started yet.'),
            self::InProgress => __('This session is under way.'),
            self::Completed => __('This session has ended and its record is read-only.'),
            self::Cancelled => __('This session was cancelled and is read-only.'),
        };
    }
}
