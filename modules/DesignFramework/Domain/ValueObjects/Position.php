<?php

namespace Modules\DesignFramework\Domain\ValueObjects;

use Modules\DesignFramework\Domain\Exceptions\InvalidPosition;
use Stringable;

/**
 * Where one piece of framework content sits among its siblings.
 *
 * Positions are 1-based and contiguous, and they are the *only* thing that
 * orders framework content. Not `id`, which would put a phase inserted later at
 * the end forever, and not `created_at`, which would do the same thing while
 * looking more reasonable.
 *
 * 1-based rather than 0-based because the numbers are read by people: a
 * framework author reordering phases sees "Phase 1", and an off-by-one between
 * what they see and what is stored is a bug waiting for somebody to fix it in
 * the wrong place.
 */
final readonly class Position implements Stringable
{
    public const FIRST = 1;

    private function __construct(public int $value) {}

    /**
     * @throws InvalidPosition
     */
    public static function fromInt(int $value): self
    {
        if ($value < self::FIRST) {
            throw InvalidPosition::forValue($value);
        }

        return new self($value);
    }

    /**
     * The position of the first item in a list.
     */
    public static function first(): self
    {
        return new self(self::FIRST);
    }

    /**
     * The position an item appended to a list of the given size would take.
     */
    public static function afterCount(int $count): self
    {
        return new self(max(0, $count) + 1);
    }

    /**
     * Read a position a caller asked for, refusing anything past the end.
     *
     * The bound is inclusive of the list's own length: moving an item to the
     * last position is legal, moving it past the end is not. Refusing rather
     * than clamping is deliberate — a clamp turns a drag that landed in the
     * wrong place into a reorder nobody asked for, silently.
     *
     * @param  int  $count  how many items are in the list, including this one
     *
     * @throws InvalidPosition
     */
    public static function within(int $value, int $count): self
    {
        if ($value < self::FIRST) {
            throw InvalidPosition::forValue($value);
        }

        if ($value > max($count, self::FIRST)) {
            throw InvalidPosition::beyondEnd($value, $count);
        }

        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
