<?php

namespace Modules\DesignFramework\Domain\Exceptions;

/**
 * Raised when a framework address is already in use.
 *
 * Framework addresses are globally unique, so unlike a game's this cannot be
 * resolved by "try a different workspace". The command that allocates one
 * derives a free address when none was supplied and only raises this when a
 * caller insisted on a specific one — so this is always a message about
 * something the author typed.
 */
final class FrameworkSlugIsTaken extends FrameworkRuleViolation
{
    private function __construct(public readonly string $slug, string $message)
    {
        parent::__construct($message);
    }

    public static function forSlug(string $slug): self
    {
        return new self($slug, __('Another framework already uses the address ":slug".', ['slug' => $slug]));
    }

    public function field(): string
    {
        return 'slug';
    }
}
