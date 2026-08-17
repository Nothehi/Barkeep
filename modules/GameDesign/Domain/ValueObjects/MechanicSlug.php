<?php

namespace Modules\GameDesign\Domain\ValueObjects;

use Illuminate\Support\Str;
use Modules\GameDesign\Domain\Exceptions\InvalidMechanicSlug;
use Stringable;

/**
 * A mechanic's address in the platform's vocabulary.
 *
 * Unique across the whole installation rather than inside anything, which is
 * the difference between this and {@see GameSlug} and the entire reason the
 * vocabulary is worth having: `worker-placement` has to mean the same term to
 * every game that claims it, or nothing can be compared across studios.
 *
 * There is no reserved list. Mechanic addresses appear at
 * `/app/mechanics/{mechanic}` with no actions hanging off the segment, so
 * there is nothing for a term to collide with — and reserving words in a
 * vocabulary of design language would eventually refuse a real mechanic for
 * the sake of a route that does not exist.
 */
final readonly class MechanicSlug implements Stringable
{
    /**
     * Short enough for real terms. "Pip" and "PvP" are things somebody will
     * want to write down.
     */
    public const MIN_LENGTH = 2;

    public const MAX_LENGTH = 96;

    private function __construct(public string $value) {}

    /**
     * Validate an address that a person supplied.
     *
     * Lossless-or-reject, matching the game and workspace address rules: the
     * value is trimmed and case folded, and anything that would change its
     * meaning is refused rather than quietly rewritten.
     *
     * @throws InvalidMechanicSlug
     */
    public static function fromString(string $value): self
    {
        $normalized = Str::lower(trim($value));

        if ($normalized === '') {
            throw InvalidMechanicSlug::forValue($value);
        }

        if (mb_strlen($normalized) < self::MIN_LENGTH) {
            throw InvalidMechanicSlug::tooShort(self::MIN_LENGTH);
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw InvalidMechanicSlug::tooLong(self::MAX_LENGTH);
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $normalized) !== 1) {
            throw InvalidMechanicSlug::forValue($value);
        }

        return new self($normalized);
    }

    /**
     * Derive an address from a mechanic's name.
     *
     * Curators type "Worker placement", not an address, so this is the common
     * path. Unlike {@see fromString()} it is allowed to rewrite: a name is
     * prose and an address is an identifier, and turning one into the other is
     * the point rather than a loss.
     *
     * @throws InvalidMechanicSlug when the name contains nothing sluggable
     */
    public static function fromName(string $name): self
    {
        $slug = trim(Str::limit(Str::slug($name), self::MAX_LENGTH, end: ''), '-');

        if (mb_strlen($slug) < self::MIN_LENGTH) {
            throw InvalidMechanicSlug::forValue($name);
        }

        return new self($slug);
    }

    /**
     * Determine whether the given string would normalise to a valid address.
     *
     * Used by the route binding, which wants a null rather than an exception
     * when somebody types nonsense into the URL bar.
     */
    public static function isValid(string $value): bool
    {
        try {
            self::fromString($value);
        } catch (InvalidMechanicSlug) {
            return false;
        }

        return true;
    }

    /**
     * Derive a candidate address by appending a suffix to this one.
     */
    public function withSuffix(int|string $suffix): self
    {
        $tail = '-'.$suffix;
        $base = Str::limit($this->value, self::MAX_LENGTH - mb_strlen($tail), end: '');

        return new self(trim($base, '-').$tail);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
