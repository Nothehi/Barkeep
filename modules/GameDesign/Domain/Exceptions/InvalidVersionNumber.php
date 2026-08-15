<?php

namespace Modules\GameDesign\Domain\Exceptions;

/**
 * Raised when a version number is not one a game could actually have.
 *
 * Version numbers are allocated by the module, never supplied by a caller,
 * so in practice this guards the value object against programmer error and
 * against a malformed number arriving from a URL.
 */
final class InvalidVersionNumber extends GameRuleViolation
{
    public static function forValue(int $value): self
    {
        return new self(__('":value" is not a valid version number.', ['value' => $value]));
    }

    public function status(): int
    {
        return 404;
    }
}
