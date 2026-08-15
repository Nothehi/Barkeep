<?php

namespace Modules\Workspace\Domain\Exceptions;

/**
 * Raised when a slug cannot be normalised into a usable workspace address.
 */
final class InvalidWorkspaceSlug extends WorkspaceRuleViolation
{
    public static function forValue(string $value): self
    {
        return new self(__('":value" is not a valid workspace address.', ['value' => $value]));
    }

    public static function tooShort(int $minimum): self
    {
        return new self(__('A workspace address must be at least :count characters long.', ['count' => $minimum]));
    }

    public static function tooLong(int $maximum): self
    {
        return new self(__('A workspace address may not be longer than :count characters.', ['count' => $maximum]));
    }

    public static function reserved(string $value): self
    {
        return new self(__('":value" is reserved and cannot be used as a workspace address.', ['value' => $value]));
    }

    /**
     * Raised when two workspaces race for the same address.
     *
     * Form validation catches the ordinary case; this covers the window
     * between that check and the insert.
     */
    public static function taken(string $value): self
    {
        return new self(__('The workspace address ":value" is already taken.', ['value' => $value]));
    }

    public function field(): string
    {
        return 'slug';
    }
}
