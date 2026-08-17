<?php

namespace Modules\GameDesign\Domain\Exceptions;

/**
 * Raised when a name cannot be turned into a usable mechanic address.
 */
final class InvalidMechanicSlug extends GameRuleViolation
{
    public static function forValue(string $value): self
    {
        return new self(__('":value" is not a valid mechanic address.', ['value' => $value]));
    }

    public static function tooShort(int $minimum): self
    {
        return new self(__('A mechanic address must be at least :count characters long.', ['count' => $minimum]));
    }

    public static function tooLong(int $maximum): self
    {
        return new self(__('A mechanic address may not be longer than :count characters.', ['count' => $maximum]));
    }

    /**
     * Raised when two curators race for one address.
     *
     * Unlike a game address this is platform-wide, so the message says so: the
     * term already exists somewhere in the vocabulary, and the answer is
     * usually to go and use it rather than to pick a different word.
     */
    public static function taken(string $value): self
    {
        return new self(__('The vocabulary already has a mechanic at ":value".', ['value' => $value]));
    }

    public function field(): string
    {
        return 'name';
    }
}
