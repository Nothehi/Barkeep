<?php

namespace Modules\GameDesign\Domain\ValueObjects;

use Modules\GameDesign\Domain\Exceptions\InvalidPlayTime;
use Stringable;

/**
 * How long a game is meant to take, in minutes.
 *
 * Minutes throughout, and stored as minutes, because every alternative is
 * worse: hours need fractions for the short end, and a mixed unit is a
 * conversion somebody eventually gets wrong. The label is where hours appear,
 * so "90" reads as "1 hour 30 minutes" without the number ever being anything
 * but minutes.
 *
 * Intended time rather than measured time. This is a constraint the designer
 * has chosen — it decides which mechanisms are even available — and not a
 * figure gathered from playtests. When playtest data can answer "how long does
 * it actually take?", that is a different number worth showing beside this one
 * rather than overwriting it.
 */
final readonly class PlayTimeRange implements Stringable
{
    /**
     * A minute is the shortest thing worth calling a playing time. Micro games
     * exist and are real designs.
     */
    public const MINIMUM = 1;

    /**
     * Twenty-four hours. Past this somebody is describing a campaign made of
     * sessions, and a campaign's length is not one game's playing time.
     */
    public const MAXIMUM = 1440;

    private function __construct(
        public int $min,
        public int $max,
    ) {}

    /**
     * Build a range from two numbers of minutes.
     *
     * @throws InvalidPlayTime
     */
    public static function fromMinutes(int $min, int $max): self
    {
        if ($min < self::MINIMUM || $max < self::MINIMUM) {
            throw InvalidPlayTime::belowMinimum(self::MINIMUM);
        }

        if ($min > self::MAXIMUM || $max > self::MAXIMUM) {
            throw InvalidPlayTime::aboveMaximum(self::MAXIMUM);
        }

        if ($max < $min) {
            throw InvalidPlayTime::outOfOrder($min, $max);
        }

        return new self($min, $max);
    }

    /**
     * Build a range from whatever a form supplied, or nothing.
     *
     * One end on its own is read as a fixed length, for the same reason a
     * single player count is: "45" means a forty-five minute game.
     *
     * @throws InvalidPlayTime
     */
    public static function fromNullableMinutes(?int $min, ?int $max): ?self
    {
        if ($min === null && $max === null) {
            return null;
        }

        return self::fromMinutes($min ?? $max, $max ?? $min);
    }

    /**
     * Determine whether the range covers exactly one length.
     */
    public function isFixed(): bool
    {
        return $this->min === $this->max;
    }

    /**
     * A human readable label for the range.
     */
    public function label(): string
    {
        return $this->isFixed()
            ? self::describe($this->min)
            : __(':min to :max', ['min' => self::describe($this->min), 'max' => self::describe($this->max)]);
    }

    public function equals(self $other): bool
    {
        return $this->min === $other->min && $this->max === $other->max;
    }

    /**
     * Render a number of minutes the way people say it.
     *
     * The one place minutes become hours. Kept here so that a screen showing a
     * playing time and a screen showing a range of them cannot disagree about
     * whether ninety minutes is "90 min" or "1h 30m".
     */
    private static function describe(int $minutes): string
    {
        if ($minutes < 60) {
            return __(':count min', ['count' => $minutes]);
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $rest === 0
            ? __(':count h', ['count' => $hours])
            : __(':hours h :minutes min', ['hours' => $hours, 'minutes' => $rest]);
    }

    public function __toString(): string
    {
        return $this->isFixed()
            ? (string) $this->min
            : $this->min.'-'.$this->max;
    }
}
