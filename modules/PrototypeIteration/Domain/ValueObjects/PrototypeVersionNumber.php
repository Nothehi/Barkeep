<?php

namespace Modules\PrototypeIteration\Domain\ValueObjects;

use Modules\PrototypeIteration\Domain\Exceptions\InvalidPrototypeVersionNumber;
use Stringable;

/**
 * The ordinal of one state of a prototype.
 *
 * Its own type rather than a reuse of GameDesign's `VersionNumber`, and the
 * reason is worth stating because the two are identical in arithmetic. A game
 * version and a prototype version are numbered independently — a game on v7 may
 * be on the third state of its second prototype — and sharing a type would put
 * this module's numbering in GameDesign's hands, where a later change to how
 * game versions are numbered would silently change how prototypes are.
 *
 * Numbers count from one and are allocated by the module, never chosen by a
 * caller: letting a client name its own would let it skip to 999 or overwrite
 * the meaning of an earlier state. That rule is enforced in
 * `CreatePrototypeVersion`; this type exists so a number that reaches the
 * domain cannot be zero, negative, or a string that happened to parse.
 */
final readonly class PrototypeVersionNumber implements Stringable
{
    public const FIRST = 1;

    private function __construct(public int $value) {}

    /**
     * @throws InvalidPrototypeVersionNumber
     */
    public static function fromInt(int $value): self
    {
        if ($value < self::FIRST) {
            throw InvalidPrototypeVersionNumber::forValue($value);
        }

        return new self($value);
    }

    /**
     * Read a version number out of a URL segment.
     *
     * Accepts only a plain run of digits, so "1.0", "v1" and "01" are all
     * treated as no such version rather than being coerced into one. Returning
     * null rather than raising is what lets the route binding turn a mistyped
     * URL into a 404 instead of a 500.
     */
    public static function fromRouteSegment(string $value): ?self
    {
        if (preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
            return null;
        }

        return new self((int) $value);
    }

    /**
     * The number a prototype's first state gets.
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
     * How a prototype state is written wherever people read it: v1, v2, v3.
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
