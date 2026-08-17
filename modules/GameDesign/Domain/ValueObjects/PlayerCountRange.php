<?php

namespace Modules\GameDesign\Domain\ValueObjects;

use Modules\GameDesign\Domain\Exceptions\InvalidPlayerCount;
use Stringable;

/**
 * How many people a game is for.
 *
 * A range rather than a number, because almost every game is one — and because
 * the difference between "2" and "2 to 5" is a design decision rather than a
 * detail: a forty-minute game for two and a two-hour game for five are
 * different designs even with identical mechanisms.
 *
 * Both ends being equal is normal and means exactly what it says. A strictly
 * two-player game says `2 to 2`, and the label renders that as "2 players"
 * rather than as a range, which is the one piece of presentation this type
 * carries — because the alternative is every screen deciding for itself.
 *
 * The "max cannot be below min" rule lives here rather than in a validation
 * rule, so a console command or a seeder is held to it too.
 */
final readonly class PlayerCountRange implements Stringable
{
    /**
     * Solitaire is a real thing designers build.
     */
    public const MINIMUM = 1;

    /**
     * Not a claim about what is possible, only about what this is a tool for.
     * A game for more than this is being run rather than played, and needs
     * facilitation rather than a player count.
     */
    public const MAXIMUM = 99;

    private function __construct(
        public int $min,
        public int $max,
    ) {}

    /**
     * Build a range from two numbers.
     *
     * @throws InvalidPlayerCount
     */
    public static function fromInts(int $min, int $max): self
    {
        if ($min < self::MINIMUM || $max < self::MINIMUM) {
            throw InvalidPlayerCount::belowMinimum(self::MINIMUM);
        }

        if ($min > self::MAXIMUM || $max > self::MAXIMUM) {
            throw InvalidPlayerCount::aboveMaximum(self::MAXIMUM);
        }

        if ($max < $min) {
            throw InvalidPlayerCount::outOfOrder($min, $max);
        }

        return new self($min, $max);
    }

    /**
     * Build a range from whatever a form supplied, or nothing.
     *
     * Null when neither end is given, which is the ordinary state of a game
     * nobody has decided about yet. A single end given on its own is read as a
     * fixed count rather than refused: somebody typing "2" into the first box
     * and leaving the second alone means a two-player game, and making them
     * type it twice would be pedantry.
     *
     * @throws InvalidPlayerCount
     */
    public static function fromNullableInts(?int $min, ?int $max): ?self
    {
        if ($min === null && $max === null) {
            return null;
        }

        return self::fromInts($min ?? $max, $max ?? $min);
    }

    /**
     * Determine whether the range covers exactly one player count.
     */
    public function isFixed(): bool
    {
        return $this->min === $this->max;
    }

    /**
     * Determine whether the game claims to support the given number of players.
     */
    public function includes(int $players): bool
    {
        return $players >= $this->min && $players <= $this->max;
    }

    public function equals(self $other): bool
    {
        return $this->min === $other->min && $this->max === $other->max;
    }

    /**
     * A human readable label for the range.
     */
    public function label(): string
    {
        if ($this->isFixed()) {
            return $this->min === 1
                ? __('Solitaire')
                : __(':count players', ['count' => $this->min]);
        }

        return __(':min to :max players', ['min' => $this->min, 'max' => $this->max]);
    }

    public function __toString(): string
    {
        return $this->isFixed()
            ? (string) $this->min
            : $this->min.'-'.$this->max;
    }
}
