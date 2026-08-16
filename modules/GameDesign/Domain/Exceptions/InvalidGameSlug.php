<?php

namespace Modules\GameDesign\Domain\Exceptions;

/**
 * Raised when a slug cannot be normalised into a usable game address.
 */
final class InvalidGameSlug extends GameRuleViolation
{
    public static function forValue(string $value): self
    {
        return new self(__('":value" is not a valid game address.', ['value' => $value]));
    }

    public static function tooShort(int $minimum): self
    {
        return new self(__('A game address must be at least :count characters long.', ['count' => $minimum]));
    }

    public static function tooLong(int $maximum): self
    {
        return new self(__('A game address may not be longer than :count characters.', ['count' => $maximum]));
    }

    public static function reserved(string $value): self
    {
        return new self(__('":value" is reserved and cannot be used as a game address.', ['value' => $value]));
    }

    /**
     * Raised when two games in the same workspace race for one address.
     *
     * Form validation catches the ordinary case; this covers the window
     * between that check and the insert. Addresses only have to be unique
     * within a workspace, so this is about the workspace the game is being
     * created in and no other.
     */
    public static function taken(string $value): self
    {
        return new self(__('This workspace already has a game at ":value".', ['value' => $value]));
    }

    public function field(): string
    {
        return 'slug';
    }
}
