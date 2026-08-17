<?php

namespace Modules\DesignFramework\Domain\ValueObjects;

use Modules\DesignFramework\Domain\Exceptions\InvalidFrameworkVersionNumber;
use Stringable;

/**
 * The ordinal of one edition of a framework.
 *
 * Versions count from one and are allocated by the module, never chosen by a
 * caller: letting a client name its own version number would let it skip to
 * 999 or overwrite the meaning of an earlier one — and version numbers here are
 * cited by games, so an overwritten one takes somebody's history with it. That
 * rule is enforced in `CreateFrameworkVersion`; this type exists so a number
 * that reaches the domain cannot be zero, negative, or a string that happened
 * to parse.
 */
final readonly class FrameworkVersionNumber implements Stringable
{
    public const FIRST = 1;

    private function __construct(public int $value) {}

    /**
     * @throws InvalidFrameworkVersionNumber
     */
    public static function fromInt(int $value): self
    {
        if ($value < self::FIRST) {
            throw InvalidFrameworkVersionNumber::forValue($value);
        }

        return new self($value);
    }

    /**
     * Read a version number out of a URL segment.
     *
     * Accepts only a plain run of digits, so "1.0", "v1" and "01" are all
     * treated as no such version rather than being coerced into one.
     */
    public static function fromRouteSegment(string $value): ?self
    {
        if (preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
            return null;
        }

        return new self((int) $value);
    }

    /**
     * The number a framework's first version gets.
     */
    public static function first(): self
    {
        return new self(self::FIRST);
    }

    /**
     * The number that follows this one.
     */
    public function next(): self
    {
        return new self($this->value + 1);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * How a version is written wherever people read it: v1, v2, v3.
     */
    public function label(): string
    {
        return 'v'.$this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
