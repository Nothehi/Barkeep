<?php

namespace Modules\GameEconomy\Domain\Exceptions;

/**
 * Raised when a variable names an action from another profile.
 *
 * The same shape as {@see ResourceDoesNotBelongToProfile} and for the same
 * reason: the foreign key proves the action exists, and only the application
 * layer can prove it belongs to the configuration the variable is part of.
 */
final class ActionDoesNotBelongToProfile extends EconomyRuleViolation
{
    private function __construct(
        public readonly string $profileId,
        public readonly string $actionId,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function forPair(string $profileId, string $actionId): self
    {
        return new self($profileId, $actionId, __('That action belongs to a different balance profile.'));
    }

    public function field(): string
    {
        return 'action_id';
    }
}
