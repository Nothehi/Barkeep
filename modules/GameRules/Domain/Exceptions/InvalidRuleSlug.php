<?php

namespace Modules\GameRules\Domain\Exceptions;

/**
 * Raised when a string that is supposed to already be a handle is not one.
 *
 * Only reachable from `RuleSlug::fromString()`, which is used where a handle is
 * read back out of storage or arrives from a caller that claims to have one.
 * Names typed by designers go through `fromName()` instead and cannot fail.
 */
final class InvalidRuleSlug extends RuleSystemViolation
{
    private function __construct(public readonly string $value, string $message)
    {
        parent::__construct($message);
    }

    public static function forValue(string $value): self
    {
        return new self($value, __('That is not a valid handle.'));
    }
}
