<?php

namespace Modules\GameRules\Domain\ValueObjects;

use Illuminate\Support\Str;
use Modules\GameRules\Domain\Exceptions\InvalidRuleSlug;
use Stringable;

/**
 * The stable handle a rule, mechanic, phase or action is known by inside a set.
 *
 * Derived from the name rather than typed, because a designer naming a phase
 * "Round Start" should not also have to invent `round_start` — and because two
 * people naming the same thing should arrive at the same handle.
 *
 * Underscores rather than hyphens, matching GameEconomy's `EconomySlug` and
 * unlike the slugs GameDesign puts in URLs. These never appear in an address:
 * every route in this module addresses a record by uuid, resolved through its
 * parent. What a handle is for is the places a rule set is read as data — a
 * cloned set matching its ancestor's phases by name, an economy action wired to
 * a rule action, whatever a studio eventually exports.
 *
 * Uniqueness is per rule set and is the database's job — a slug does not know
 * what else exists.
 */
final readonly class RuleSlug implements Stringable
{
    public const MAX_LENGTH = 80;

    private function __construct(public string $value) {}

    /**
     * Build a slug from an already valid string.
     *
     * @throws InvalidRuleSlug
     */
    public static function fromString(string $value): self
    {
        $candidate = mb_strtolower(trim($value));

        if (! self::isValid($candidate)) {
            throw InvalidRuleSlug::forValue($value);
        }

        return new self($candidate);
    }

    /**
     * Derive a slug from whatever the designer called the thing.
     *
     * Falls back to a generated handle when the name slugs to nothing at all —
     * a phase called "???", or one named with emoji. Refusing it instead would
     * mean a designer could not call a phase what they call it.
     *
     * Persian names do *not* hit that path: `Str::slug` transliterates them, so
     * "برپایی" becomes `brpayy`. Unreadable, and fine here, because these handles
     * never appear in a URL — every route in this module addresses a record by
     * uuid. Where a slug *is* a URL segment, as in GameDesign, the seeders carry
     * an explicit Latin one instead; see `.ai/rules/seeders.md`.
     */
    public static function fromName(string $name): self
    {
        $slug = Str::slug($name, '_');

        if ($slug === '') {
            return new self('rule_'.mb_strtolower(Str::random(8)));
        }

        return new self(mb_substr($slug, 0, self::MAX_LENGTH));
    }

    /**
     * Determine whether a string is shaped like a handle.
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
