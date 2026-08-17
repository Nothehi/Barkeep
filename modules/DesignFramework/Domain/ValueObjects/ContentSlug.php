<?php

namespace Modules\DesignFramework\Domain\ValueObjects;

use Illuminate\Support\Str;
use Modules\DesignFramework\Domain\Exceptions\InvalidContentSlug;
use Stringable;

/**
 * The address of one piece of framework content inside its version.
 *
 * Every phase, principle, criterion, practice, prompt and checklist has one,
 * and it is unique within the framework version — or, for a checklist item,
 * within its checklist. Phases put theirs in URLs
 * (`/versions/1/phases/core-loop`); the rest use theirs as the stable handle a
 * seeder, an import or a future export can address content by, so that
 * rebuilding v1 twice produces the same identifiers.
 *
 * Content is almost always created by typing a title rather than an address, so
 * {@see fromTitle()} is the common path and derives one. Uniqueness is not this
 * type's business: `ContentSlugAllocator` resolves collisions, because it needs
 * to know what already exists.
 */
final readonly class ContentSlug implements Stringable
{
    public const MIN_LENGTH = 2;

    public const MAX_LENGTH = 96;

    /**
     * Addresses the framework builder and phase routes need for themselves.
     *
     * @var list<string>
     */
    private const RESERVED = [
        'checklists', 'create', 'criteria', 'edit', 'new', 'phases',
        'practices', 'principles', 'progress', 'prompts', 'reorder',
    ];

    private function __construct(public string $value) {}

    /**
     * Validate an address that a person supplied.
     *
     * @throws InvalidContentSlug
     */
    public static function fromString(string $value): self
    {
        $normalized = Str::lower(trim($value));

        if ($normalized === '') {
            throw InvalidContentSlug::forValue($value);
        }

        if (mb_strlen($normalized) < self::MIN_LENGTH) {
            throw InvalidContentSlug::tooShort(self::MIN_LENGTH);
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw InvalidContentSlug::tooLong(self::MAX_LENGTH);
        }

        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $normalized) !== 1) {
            throw InvalidContentSlug::forValue($value);
        }

        if (in_array($normalized, self::RESERVED, strict: true)) {
            throw InvalidContentSlug::reserved($normalized);
        }

        return new self($normalized);
    }

    /**
     * Derive an address from a title.
     *
     * Titles here are often whole sentences — "Does the game provide
     * meaningful decisions?" — so the result is truncated at a word boundary
     * rather than mid-word, which is what keeps `does-the-game-provide` legible
     * instead of `does-the-game-provi`.
     *
     * @throws InvalidContentSlug when the title contains nothing sluggable
     */
    public static function fromTitle(string $title): self
    {
        $slug = trim(Str::limit(Str::slug($title), self::MAX_LENGTH, end: ''), '-');

        if (mb_strlen($slug) < self::MIN_LENGTH) {
            throw InvalidContentSlug::forValue($title);
        }

        return in_array($slug, self::RESERVED, strict: true)
            ? new self($slug.'-item')
            : new self($slug);
    }

    /**
     * Determine whether the given string would normalise to a valid address.
     */
    public static function isValid(string $value): bool
    {
        try {
            self::fromString($value);
        } catch (InvalidContentSlug) {
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
