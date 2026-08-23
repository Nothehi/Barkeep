<?php

namespace Modules\GameRules\Domain\Exceptions;

/**
 * Raised when a name the rule set identifies something by is already in use.
 *
 * Conditions, condition groups, triggers and the three kinds of outcome are
 * addressed by name rather than by a derived handle, because they are referred
 * to in prose: a transition guarded by "all players have passed" is readable in
 * a way one guarded by `cond_4f2a` is not. The name is therefore the identity,
 * and a duplicate is refused rather than silently disambiguated.
 */
final class RuleNameIsTaken extends RuleSystemViolation
{
    private function __construct(public readonly string $name, string $message)
    {
        parent::__construct($message);
    }

    public static function forCondition(string $name): self
    {
        return new self($name, __('This rule set already has a condition with that name.'));
    }

    public static function forConditionGroup(string $name): self
    {
        return new self($name, __('This rule set already has a condition group with that name.'));
    }

    public static function forTrigger(string $name): self
    {
        return new self($name, __('This rule set already has a trigger with that name.'));
    }

    public static function forVictoryCondition(string $name): self
    {
        return new self($name, __('This rule set already has a victory condition with that name.'));
    }

    public static function forDefeatCondition(string $name): self
    {
        return new self($name, __('This rule set already has a defeat condition with that name.'));
    }

    public static function forGameEndCondition(string $name): self
    {
        return new self($name, __('This rule set already has an end condition with that name.'));
    }

    public static function forRuleSet(string $name): self
    {
        return new self($name, __('This version already has a rule set with that name.'));
    }

    public function field(): string
    {
        return 'name';
    }
}
