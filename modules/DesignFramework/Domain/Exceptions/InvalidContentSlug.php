<?php

namespace Modules\DesignFramework\Domain\Exceptions;

/**
 * Raised when a piece of framework content is given an unusable address.
 *
 * Content addresses are unique inside a framework version, and phases use
 * theirs in URLs. The rules are the same as a framework's apart from the
 * reserved words, which belong to different routes.
 */
final class InvalidContentSlug extends FrameworkRuleViolation
{
    public static function forValue(string $value): self
    {
        return new self(__('":value" is not a valid address.', ['value' => $value]));
    }

    public static function tooShort(int $minimum): self
    {
        return new self(__('An address must be at least :min characters long.', ['min' => $minimum]));
    }

    public static function tooLong(int $maximum): self
    {
        return new self(__('An address may not be longer than :max characters.', ['max' => $maximum]));
    }

    public static function reserved(string $value): self
    {
        return new self(__('":value" is reserved and cannot be used as an address.', ['value' => $value]));
    }

    public function field(): string
    {
        return 'slug';
    }
}
