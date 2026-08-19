<?php

namespace Modules\GameEconomy\Domain\Exceptions;

/**
 * Raised when a profile or scenario name is already in use where it has to be
 * unique.
 *
 * Profiles are unique per game version and scenarios per profile, in both cases
 * because the name is the only thing that tells two of them apart on screen — a
 * design state carrying two profiles both called "Tuning pass" is a list nobody
 * can use.
 */
final class EconomyNameIsTaken extends EconomyRuleViolation
{
    private function __construct(public readonly string $name, string $message)
    {
        parent::__construct($message);
    }

    public static function forProfile(string $name): self
    {
        return new self($name, __('This version already has a balance profile with that name.'));
    }

    public static function forScenario(string $name): self
    {
        return new self($name, __('This balance profile already has a scenario with that name.'));
    }

    public function field(): string
    {
        return 'name';
    }
}
