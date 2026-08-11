<?php

namespace Modules\Workspace\Domain\ValueObjects;

use Illuminate\Support\Str;
use Modules\Workspace\Domain\Exceptions\InvalidWorkspaceSlug;
use Stringable;

/**
 * A workspace's human facing address.
 *
 * The slug appears in URLs, so what counts as "the same workspace address" is
 * decided here once rather than at each call site. Normalisation is
 * deliberately lossless-or-reject: a supplied slug is cleaned up, but if
 * cleaning it up would change its meaning the value is refused instead.
 */
final readonly class WorkspaceSlug implements Stringable
{
    public const MIN_LENGTH = 3;

    public const MAX_LENGTH = 48;

    /**
     * Addresses the platform needs for itself.
     *
     * Workspace URLs sit next to application routes, so a workspace called
     * "settings" would be indistinguishable from the settings screen.
     *
     * @var list<string>
     */
    private const RESERVED = [
        'admin', 'api', 'app', 'assets', 'auth', 'billing', 'build', 'dashboard',
        'help', 'home', 'login', 'logout', 'new', 'register', 'settings', 'signup',
        'storage', 'support', 'system', 'up', 'user', 'users', 'workspace', 'workspaces',
    ];

    private function __construct(public string $value) {}

    /**
     * Validate a slug that a person supplied.
     *
     * Trimming and case folding are applied because they cannot change which
     * workspace is meant. Anything else — spaces, punctuation, accents — is an
     * error rather than something to silently rewrite.
     *
     * @throws InvalidWorkspaceSlug
     */
    public static function fromString(string $value): self
    {
        $normalized = Str::lower(trim($value));

        if ($normalized === '') {
            throw InvalidWorkspaceSlug::forValue($value);
        }

        if (mb_strlen($normalized) < self::MIN_LENGTH) {
            throw InvalidWorkspaceSlug::tooShort(self::MIN_LENGTH);
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw InvalidWorkspaceSlug::tooLong(self::MAX_LENGTH);
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $normalized) !== 1) {
            throw InvalidWorkspaceSlug::forValue($value);
        }

        if (in_array($normalized, self::RESERVED, strict: true)) {
            throw InvalidWorkspaceSlug::reserved($normalized);
        }

        return new self($normalized);
    }

    /**
     * Derive a slug from a workspace name.
     *
     * Used only when no slug was supplied. Unlike {@see fromString()} this is
     * allowed to rewrite its input, because the caller asked for a suggestion
     * rather than for a specific address.
     *
     * @throws InvalidWorkspaceSlug when the name contains nothing sluggable
     */
    public static function fromName(string $name): self
    {
        $slug = Str::limit(Str::slug($name), self::MAX_LENGTH, end: '');
        $slug = trim($slug, '-');

        if (mb_strlen($slug) < self::MIN_LENGTH) {
            throw InvalidWorkspaceSlug::forValue($name);
        }

        return in_array($slug, self::RESERVED, strict: true)
            ? new self($slug.'-workspace')
            : new self($slug);
    }

    /**
     * Determine whether the given string would normalise to this slug.
     */
    public static function isValid(string $value): bool
    {
        try {
            self::fromString($value);
        } catch (InvalidWorkspaceSlug) {
            return false;
        }

        return true;
    }

    /**
     * Derive a candidate slug by appending a suffix to this address.
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
