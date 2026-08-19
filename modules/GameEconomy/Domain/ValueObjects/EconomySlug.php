<?php

namespace Modules\GameEconomy\Domain\ValueObjects;

use Illuminate\Support\Str;
use Modules\GameEconomy\Domain\Exceptions\InvalidEconomySlug;
use Stringable;

/**
 * The stable handle a resource, action or variable is known by inside a profile.
 *
 * Derived from the name rather than typed, because a designer naming a resource
 * "Action Points" should not also have to invent `action_points` — and because
 * two people naming the same thing should arrive at the same handle.
 *
 * Underscores rather than hyphens, unlike the slugs GameDesign puts in URLs.
 * These never appear in an address; they appear in a variable table beside
 * `wood_harvest_amount` and in whatever a studio eventually exports, where the
 * shape people expect is the one a spreadsheet column has.
 *
 * Uniqueness is per profile and is the database's job — a slug does not know
 * what else exists.
 */
final readonly class EconomySlug implements Stringable
{
    public const MAX_LENGTH = 80;

    private function __construct(public string $value) {}

    /**
     * Build a slug from an already valid string.
     *
     * @throws InvalidEconomySlug
     */
    public static function fromString(string $value): self
    {
        $candidate = mb_strtolower(trim($value));

        if (! self::isValid($candidate)) {
            throw InvalidEconomySlug::forValue($value);
        }

        return new self($candidate);
    }

    /**
     * Derive a slug from whatever the designer called the thing.
     *
     * Falls back to a generated handle when the name slugs to nothing at all,
     * which is what happens when it is written entirely in a script `Str::slug`
     * has no transliteration for. Persian is a supported locale of this
     * application, so that is a real case rather than a theoretical one, and
     * refusing the name would mean a designer could not call a resource what
     * they call it.
     */
    public static function fromName(string $name): self
    {
        $slug = Str::slug($name, '_');

        if ($slug === '') {
            return new self('item_'.mb_strtolower(Str::random(8)));
        }

        return new self(mb_substr($slug, 0, self::MAX_LENGTH));
    }

    /**
     * Determine whether a string is shaped like a slug.
     */
    public static function isValid(string $value): bool
    {
        return preg_match('/^[a-z0-9]+(_[a-z0-9]+)*$/', $value) === 1
            && mb_strlen($value) <= self::MAX_LENGTH;
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
