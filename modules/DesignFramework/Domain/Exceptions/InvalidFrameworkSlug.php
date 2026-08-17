<?php

namespace Modules\DesignFramework\Domain\Exceptions;

/**
 * Raised when a framework address is not one this platform will accept.
 *
 * Attributed to the `slug` field, so a framework author is told next to the
 * input rather than through a toast.
 */
final class InvalidFrameworkSlug extends FrameworkRuleViolation
{
    public static function forValue(string $value): self
    {
        return new self(__('":value" is not a valid framework address.', ['value' => $value]));
    }

    public static function tooShort(int $minimum): self
    {
        return new self(__('A framework address must be at least :min characters long.', ['min' => $minimum]));
    }

    public static function tooLong(int $maximum): self
    {
        return new self(__('A framework address may not be longer than :max characters.', ['max' => $maximum]));
    }

    public static function reserved(string $value): self
    {
        return new self(__('":value" is reserved and cannot be used as a framework address.', ['value' => $value]));
    }

    public function field(): string
    {
        return 'slug';
    }
}
