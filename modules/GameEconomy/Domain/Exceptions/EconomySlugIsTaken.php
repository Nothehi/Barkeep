<?php

namespace Modules\GameEconomy\Domain\Exceptions;

use Modules\GameEconomy\Domain\ValueObjects\EconomySlug;

/**
 * Raised when a name would produce a handle the profile already uses.
 *
 * Slugs are derived rather than typed, so this is reported against `name` — the
 * field the designer actually filled in. Telling somebody that `wood_2` is taken
 * when they typed "Wood" would be the derivation leaking out of the module.
 */
final class EconomySlugIsTaken extends EconomyRuleViolation
{
    private function __construct(public readonly EconomySlug $slug, string $message)
    {
        parent::__construct($message);
    }

    public static function forResource(EconomySlug $slug): self
    {
        return new self($slug, __('This balance profile already has a resource with that name.'));
    }

    public static function forAction(EconomySlug $slug): self
    {
        return new self($slug, __('This balance profile already has an action with that name.'));
    }

    public static function forVariable(EconomySlug $slug): self
    {
        return new self($slug, __('This balance profile already has a variable with that name.'));
    }

    public function field(): string
    {
        return 'name';
    }
}
