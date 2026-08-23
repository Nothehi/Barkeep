<?php

namespace Modules\GameRules\Domain\Exceptions;

use Modules\GameRules\Domain\ValueObjects\RuleSlug;

/**
 * Raised when a name would produce a handle the rule set already uses.
 *
 * Handles are derived rather than typed, so this is reported against `name` —
 * the field the designer actually filled in. Telling somebody that `combat_2` is
 * taken when they typed "Combat" would be the derivation leaking out of the
 * module.
 */
final class RuleSlugIsTaken extends RuleSystemViolation
{
    private function __construct(public readonly RuleSlug $slug, string $message)
    {
        parent::__construct($message);
    }

    public static function forRule(RuleSlug $slug): self
    {
        return new self($slug, __('This rule set already has a rule with that name.'));
    }

    public static function forMechanic(RuleSlug $slug): self
    {
        return new self($slug, __('This rule set already names a mechanic that way.'));
    }

    public static function forPhase(RuleSlug $slug): self
    {
        return new self($slug, __('This rule set already has a phase with that name.'));
    }

    public static function forAction(RuleSlug $slug): self
    {
        return new self($slug, __('This rule set already has an action with that name.'));
    }

    public function field(): string
    {
        return 'name';
    }
}
