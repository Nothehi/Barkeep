<?php

namespace Modules\PrototypeIteration\Domain\Exceptions;

/**
 * Raised when a prototype version number is not a number a version can have.
 *
 * Versions count from one. There is no v0 and there is no negative state — a
 * value that reached the domain and failed this check came from data written
 * outside the module's own allocation, which is worth surfacing loudly rather
 * than rounding up.
 */
final class InvalidPrototypeVersionNumber extends IterationRuleViolation
{
    private function __construct(public readonly int $value, string $message)
    {
        parent::__construct($message);
    }

    public static function forValue(int $value): self
    {
        return new self($value, __('A prototype version number must be 1 or greater.'));
    }
}
