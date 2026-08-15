<?php

namespace Modules\GameDesign\Domain\ValueObjects;

use Illuminate\Support\Str;
use Modules\GameDesign\Domain\Exceptions\InvalidGameSlug;
use Stringable;

/**
 * A game's address inside its workspace.
 *
 * Game addresses are unique per workspace, not globally: two studios may both
 * be working on something called "bears-and-bridges" without either having to
 * rename. That constraint lives on the table; this type only decides what a
 * well-formed address looks like.
 *
 * Normalisation is lossless-or-reject, matching the workspace address rules:
 * a supplied slug is trimmed and case folded, but anything that would change
 * its meaning is refused rather than silently rewritten.
 */
final readonly class GameSlug implements Stringable
{
    /**
     * Shorter than a workspace address on purpose. "Go", "Uno" and "Hive"
     * are real games, and refusing their names would be absurd.
     */
    public const MIN_LENGTH = 2;

    public const MAX_LENGTH = 64;

    /**
     * Addresses the game routes need for themselves.
     *
     * A game sits at `/app/workspaces/{workspace}/games/{game}`, with its own
     * actions hanging off the same segment. Reserving those words keeps a
     * game called "Settings" from becoming indistinguishable from the screen
     * that configures one.
     *
     * @var list<string>
     */
    private const RESERVED = [
        'archive', 'create', 'design-phase', 'edit', 'new', 'settings',
        'status', 'version', 'versions',
    ];

    private function __construct(public string $value) {}

    /**
     * Validate an address that a person supplied.
     *
     * @throws InvalidGameSlug
     */
    public static function fromString(string $value): self
    {
        $normalized = Str::lower(trim($value));

        if ($normalized === '') {
            throw InvalidGameSlug::forValue($value);
        }

        if (mb_strlen($normalized) < self::MIN_LENGTH) {
            throw InvalidGameSlug::tooShort(self::MIN_LENGTH);
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw InvalidGameSlug::tooLong(self::MAX_LENGTH);
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $normalized) !== 1) {
            throw InvalidGameSlug::forValue($value);
        }

        if (in_array($normalized, self::RESERVED, strict: true)) {
            throw InvalidGameSlug::reserved($normalized);
        }

        return new self($normalized);
    }

    /**
     * Derive an address from a game's name.
     *
     * Used only when no address was supplied. Unlike {@see fromString()} this
     * is allowed to rewrite its input, because the caller asked for a
     * suggestion rather than for a specific address — "Bears & Bridges"
     * becomes "bears-bridges" rather than an error.
     *
     * @throws InvalidGameSlug when the name contains nothing sluggable
     */
    public static function fromName(string $name): self
    {
        $slug = Str::limit(Str::slug($name), self::MAX_LENGTH, end: '');
        $slug = trim($slug, '-');

        if (mb_strlen($slug) < self::MIN_LENGTH) {
            throw InvalidGameSlug::forValue($name);
        }

        return in_array($slug, self::RESERVED, strict: true)
            ? new self($slug.'-game')
            : new self($slug);
    }

    /**
     * Determine whether the given string would normalise to a valid address.
     */
    public static function isValid(string $value): bool
    {
        try {
            self::fromString($value);
        } catch (InvalidGameSlug) {
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
