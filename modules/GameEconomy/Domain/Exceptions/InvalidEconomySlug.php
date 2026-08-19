<?php

namespace Modules\GameEconomy\Domain\Exceptions;

use InvalidArgumentException;

/**
 * A string that could not be a resource, action or variable handle.
 *
 * Not part of the {@see EconomyRuleViolation} family on purpose. Slugs are
 * derived from names rather than typed, so a bad one is a programming mistake
 * rather than something a designer did — and reporting it as a 422 next to a
 * form field would tell them to fix something they never touched.
 */
final class InvalidEconomySlug extends InvalidArgumentException
{
    public static function forValue(string $value): self
    {
        return new self("[{$value}] is not a valid economy slug.");
    }
}
