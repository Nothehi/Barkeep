<?php

namespace Modules\Playtesting\Domain\ValueObjects;

use DateTimeInterface;

/**
 * How long a session actually ran.
 *
 * Derived from the two timestamps rather than stored beside them, which is the
 * whole point: a persisted duration is a third fact that can disagree with the
 * two it was computed from, and the disagreement is only ever discovered later
 * when somebody is trying to work out why a playtest report looks wrong.
 *
 * "Actually" is doing work in that first line. A session carries a planned
 * time as well as a real one, and only the real one produces a duration —
 * a game that was scheduled for an hour and ran for two is exactly the kind of
 * thing a playtest is meant to find out.
 */
final readonly class SessionDuration
{
    private const SECONDS_PER_MINUTE = 60;

    private const SECONDS_PER_HOUR = 3600;

    private function __construct(public int $seconds) {}

    /**
     * Measure the span between a start and an end.
     *
     * Returns null unless both ends are known, so "still running" and "never
     * started" are absences rather than zeroes. A zero would average into a
     * playtest's figures as a session that took no time at all.
     *
     * An end before its start is treated the same way. It means a timestamp
     * was corrected badly somewhere, and a negative duration would poison
     * every average it reached.
     */
    public static function between(?DateTimeInterface $start, ?DateTimeInterface $end): ?self
    {
        if ($start === null || $end === null) {
            return null;
        }

        $seconds = $end->getTimestamp() - $start->getTimestamp();

        return $seconds < 0 ? null : new self($seconds);
    }

    /**
     * Build a duration from a number of seconds, for averages and totals.
     */
    public static function fromSeconds(int $seconds): self
    {
        return new self(max(0, $seconds));
    }

    /**
     * The duration in whole minutes, rounded down.
     */
    public function minutes(): int
    {
        return intdiv($this->seconds, self::SECONDS_PER_MINUTE);
    }

    /**
     * How the duration is written wherever people read it: 1h 15m, 45m, 30s.
     *
     * Board game sessions are talked about in hours and minutes, so seconds
     * only appear when there is nothing else to show — which in practice means
     * a session somebody started and ended by accident.
     */
    public function label(): string
    {
        $hours = intdiv($this->seconds, self::SECONDS_PER_HOUR);
        $minutes = intdiv($this->seconds % self::SECONDS_PER_HOUR, self::SECONDS_PER_MINUTE);

        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }

        return $minutes > 0 ? "{$minutes}m" : "{$this->seconds}s";
    }
}
