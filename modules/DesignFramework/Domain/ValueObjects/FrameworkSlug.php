<?php

namespace Modules\DesignFramework\Domain\ValueObjects;

use Illuminate\Support\Str;
use Modules\DesignFramework\Domain\Exceptions\InvalidFrameworkSlug;
use Stringable;

/**
 * A framework's address.
 *
 * Globally unique, unlike a game's. A framework is a methodology the platform
 * publishes rather than a document inside a workspace, so there is no tenant to
 * scope the address to and two frameworks may not share one.
 *
 * Normalisation is lossless-or-reject, matching the workspace and game address
 * rules: a supplied slug is trimmed and case folded, but anything that would
 * change its meaning is refused rather than silently rewritten.
 */
final readonly class FrameworkSlug implements Stringable
{
    public const MIN_LENGTH = 2;

    public const MAX_LENGTH = 96;

    /**
     * Addresses the framework routes need for themselves.
     *
     * A framework sits at `/app/frameworks/{framework}` with its own actions
     * hanging off the same segment, so reserving these keeps a framework called
     * "versions" from becoming indistinguishable from the list of them.
     *
     * @var list<string>
     */
    private const RESERVED = [
        'archive', 'create', 'edit', 'new', 'publish', 'settings',
        'version', 'versions',
    ];

    private function __construct(public string $value) {}

    /**
     * Validate an address that a person supplied.
     *
     * @throws InvalidFrameworkSlug
     */
    public static function fromString(string $value): self
    {
        $normalized = Str::lower(trim($value));

        if ($normalized === '') {
            throw InvalidFrameworkSlug::forValue($value);
        }

        if (mb_strlen($normalized) < self::MIN_LENGTH) {
            throw InvalidFrameworkSlug::tooShort(self::MIN_LENGTH);
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw InvalidFrameworkSlug::tooLong(self::MAX_LENGTH);
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $normalized) !== 1) {
            throw InvalidFrameworkSlug::forValue($value);
        }

        if (in_array($normalized, self::RESERVED, strict: true)) {
            throw InvalidFrameworkSlug::reserved($normalized);
        }

        return new self($normalized);
    }

    /**
     * Derive an address from a framework's name.
     *
     * Used only when no address was supplied. Unlike {@see fromString()} this
     * is allowed to rewrite its input, because the caller asked for a
     * suggestion rather than for a specific address.
     *
     * @throws InvalidFrameworkSlug when the name contains nothing sluggable
     */
    public static function fromName(string $name): self
    {
        $slug = trim(Str::limit(Str::slug($name), self::MAX_LENGTH, end: ''), '-');

        if (mb_strlen($slug) < self::MIN_LENGTH) {
            throw InvalidFrameworkSlug::forValue($name);
        }

        return in_array($slug, self::RESERVED, strict: true)
            ? new self($slug.'-framework')
            : new self($slug);
    }

    /**
     * Determine whether the given string would normalise to a valid address.
     */
    public static function isValid(string $value): bool
    {
        try {
            self::fromString($value);
        } catch (InvalidFrameworkSlug) {
            return false;
        }

        return true;
    }

    /**
     * Derive a candidate address by appending a suffix to this one.
     *
     * The base is shortened to make room rather than the result being allowed
     * to run past the length limit, so a long name still produces a usable
     * address on its hundredth collision.
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
