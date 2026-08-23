<?php

namespace Modules\GameRules\Domain\Exceptions;

/**
 * Raised when a requirement or an effect names both of its possible owners, or
 * neither.
 *
 * Both tables carry a nullable `rule_id` and a nullable `action_id`, and exactly
 * one of them is meant to be set. The schema cannot say "exactly one" portably,
 * so the commands do.
 *
 * Neither mistake is harmless. An effect belonging to a rule *and* an action
 * happens twice and is edited in one place; an effect belonging to neither never
 * happens at all, which is what the validator reports as an error for records
 * that predate this check.
 */
final class AmbiguousOwner extends RuleSystemViolation
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forRequirement(): self
    {
        return new self(__('A requirement belongs to either a rule or an action, not both.'));
    }

    public static function forEffect(): self
    {
        return new self(__('An effect belongs to either a rule or an action, not both.'));
    }

    public static function withoutOwnerForRequirement(): self
    {
        return new self(__('Choose the rule or the action this requirement belongs to.'));
    }

    public static function withoutOwnerForEffect(): self
    {
        return new self(__('Choose the rule or the action this effect belongs to.'));
    }

    public function field(): string
    {
        return 'rule_id';
    }
}
