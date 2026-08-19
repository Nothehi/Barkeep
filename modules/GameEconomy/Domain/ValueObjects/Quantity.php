<?php

namespace Modules\GameEconomy\Domain\ValueObjects;

use InvalidArgumentException;
use Stringable;

/**
 * An exact amount of something.
 *
 * Every number in this module that a designer typed goes through here, and the
 * reason is section 47 of the brief: floating point is wrong for this domain.
 * Not slightly wrong — a victory threshold that reads back as 19.999999 is a
 * support ticket, and a conversion ratio computed in binary fractions will tell
 * a studio that two wood makes 0.9999999 gold. The columns are DECIMAL and the
 * arithmetic is done in base ten with bcmath, so the answer a designer gets is
 * the answer they would get on paper.
 *
 * The value is carried as a string throughout for the same reason. There is no
 * `toFloat()`: the only way out is a string, and anything that wants to compare
 * amounts uses {@see compareTo()} rather than casting two of these to a type
 * that cannot represent them.
 *
 * Six decimal places, matching the columns exactly. A scale mismatch between the
 * arithmetic and the storage is how a total that was correct in memory becomes
 * a total that is wrong after a reload.
 */
final readonly class Quantity implements Stringable
{
    /**
     * The number of decimal places every amount in the module carries.
     *
     * The same figure as the `decimal(20, 6)` columns. Changing one without the
     * other would mean the arithmetic and the storage disagreed about what a
     * number is.
     */
    public const SCALE = 6;

    /**
     * @param  numeric-string  $value  already normalised to {@see SCALE} places
     */
    private function __construct(public string $value) {}

    /**
     * Nothing.
     */
    public static function zero(): self
    {
        return new self(self::normalise('0'));
    }

    /**
     * Build an amount from anything that arrived carrying one.
     *
     * Floats are accepted because Laravel hands them over from JSON request
     * bodies, and refusing them would only mean every caller doing the same cast
     * less carefully. The float is turned into a decimal string immediately and
     * never participates in arithmetic.
     *
     * @throws InvalidArgumentException when the value is not a number
     */
    public static function from(int|float|string $value): self
    {
        $text = is_float($value)
            ? number_format($value, self::SCALE, '.', '')
            : trim((string) $value);

        if (! self::isNumeric($text)) {
            throw new InvalidArgumentException("[{$text}] is not a decimal amount.");
        }

        return new self(self::normalise($text));
    }

    /**
     * Build an amount, or null when there was nothing to build one from.
     *
     * The common case at the edges of this module: `min_value`, `max_value`,
     * `step` and `starting_value` are all nullable, and null means "unbounded"
     * rather than zero.
     */
    public static function fromNullable(int|float|string|null $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::from($value);
    }

    /**
     * Determine whether a value could be read as an amount at all.
     */
    public static function isValid(int|float|string|null $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return self::isNumeric(is_float($value)
            ? number_format($value, self::SCALE, '.', '')
            : trim((string) $value));
    }

    /**
     * The sum of this amount and another.
     */
    public function plus(self $other): self
    {
        return new self(bcadd($this->value, $other->value, self::SCALE));
    }

    /**
     * This amount less another.
     */
    public function minus(self $other): self
    {
        return new self(bcsub($this->value, $other->value, self::SCALE));
    }

    /**
     * This amount multiplied by another.
     */
    public function times(self $other): self
    {
        return new self(bcmul($this->value, $other->value, self::SCALE));
    }

    /**
     * This amount divided by another, or null when the divisor is zero.
     *
     * Null rather than an exception, because dividing by zero is an ordinary
     * outcome here rather than a programming mistake: a conversion ratio asks
     * "how much of B does one A buy?" and an action that costs nothing is a
     * perfectly normal thing for a designer to have configured. The analysis
     * reports it as a finding; it must not raise.
     */
    public function dividedBy(self $other): ?self
    {
        if ($other->isZero()) {
            return null;
        }

        return new self(bcdiv($this->value, $other->value, self::SCALE));
    }

    /**
     * This amount with its sign flipped.
     */
    public function negated(): self
    {
        return self::zero()->minus($this);
    }

    /**
     * This amount without its sign.
     */
    public function absolute(): self
    {
        return $this->isNegative() ? $this->negated() : $this;
    }

    /**
     * Compare two amounts: -1, 0 or 1.
     */
    public function compareTo(self $other): int
    {
        return bccomp($this->value, $other->value, self::SCALE);
    }

    public function equals(self $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    public function isLessThan(self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    public function isZero(): bool
    {
        return $this->compareTo(self::zero()) === 0;
    }

    public function isPositive(): bool
    {
        return $this->compareTo(self::zero()) > 0;
    }

    public function isNegative(): bool
    {
        return $this->compareTo(self::zero()) < 0;
    }

    /**
     * Determine whether this amount falls inside the given bounds.
     *
     * Either bound may be absent, which means unbounded on that side — so a
     * variable with only a floor is checked against the floor and nothing else.
     */
    public function isWithin(?self $minimum, ?self $maximum): bool
    {
        if ($minimum !== null && $this->isLessThan($minimum)) {
            return false;
        }

        return ! ($maximum !== null && $this->isGreaterThan($maximum));
    }

    /**
     * The amount as it should be stored: full scale, so it round-trips.
     */
    public function toStorage(): string
    {
        return $this->value;
    }

    /**
     * The amount as somebody would write it.
     *
     * Trailing zeros are dropped, because a designer who typed 3 should read 3
     * rather than 3.000000 — the scale is a storage concern and putting it on
     * screen makes an integer economy look like an accounting system.
     */
    public function label(): string
    {
        if (! str_contains($this->value, '.')) {
            return $this->value;
        }

        $trimmed = rtrim(rtrim($this->value, '0'), '.');

        return $trimmed === '' || $trimmed === '-' ? '0' : $trimmed;
    }

    public function __toString(): string
    {
        return $this->label();
    }

    /**
     * Pad or truncate a numeric string to the module's scale.
     *
     * @param  numeric-string  $value
     * @return numeric-string
     */
    private static function normalise(string $value): string
    {
        return bcadd($value, '0', self::SCALE);
    }

    /**
     * Determine whether a string is a plain decimal number.
     *
     * Deliberately stricter than `is_numeric`: scientific notation, hexadecimal
     * and leading `+` all pass that check and none of them is something a
     * designer typed into a cost field.
     *
     * The assertion is what makes the rest of this class type-safe without a
     * cast anywhere: past this check the string is known to be arithmetic
     * input, which is exactly what bcmath asks for.
     *
     * @phpstan-assert-if-true numeric-string $value
     */
    private static function isNumeric(string $value): bool
    {
        return preg_match('/^-?\d+(\.\d+)?$/', $value) === 1;
    }
}
