<?php

namespace Modules\Playtesting\Domain\ValueObjects;

use Modules\Playtesting\Domain\Exceptions\InvalidFeedbackRating;

/**
 * How much somebody enjoyed a session, on the one scale the platform has.
 *
 * One to five, and nothing else. The scale is fixed rather than configurable
 * because ratings that mean different things in different playtests cannot be
 * averaged, and averaging them is the only thing the number is for.
 *
 * It is a whole number by construction: a 4.5 is a designer trying to express
 * something the scale does not capture, and that belongs in the feedback text
 * where somebody will actually read it.
 *
 * Ratings are optional throughout. A participant who said "the combat was fun"
 * and did not put a number on it has given real feedback, and refusing it for
 * want of a score would lose it.
 */
final readonly class FeedbackRating
{
    public const MIN = 1;

    public const MAX = 5;

    private function __construct(public int $value) {}

    /**
     * Build a rating, refusing anything outside the scale.
     *
     * @throws InvalidFeedbackRating
     */
    public static function fromInt(int $value): self
    {
        if (! self::isValid($value)) {
            throw InvalidFeedbackRating::outOfRange($value);
        }

        return new self($value);
    }

    /**
     * Build a rating from optional input, keeping "no rating" as an answer.
     *
     * @throws InvalidFeedbackRating
     */
    public static function fromNullable(?int $value): ?self
    {
        return $value === null ? null : self::fromInt($value);
    }

    /**
     * Determine whether a number is on the scale, without throwing.
     *
     * Used by validation, which wants to report the problem next to the field
     * rather than to raise it.
     */
    public static function isValid(int $value): bool
    {
        return $value >= self::MIN && $value <= self::MAX;
    }

    /**
     * Every point on the scale, lowest first.
     *
     * @return list<int>
     */
    public static function scale(): array
    {
        return range(self::MIN, self::MAX);
    }

    /**
     * How the rating is written wherever people read it: 4/5.
     */
    public function label(): string
    {
        return $this->value.'/'.self::MAX;
    }
}
