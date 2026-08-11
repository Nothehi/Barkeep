<?php

namespace Modules\Identity\Domain\Enums;

/**
 * The lifecycle state of a user account.
 *
 * Identity only enforces the state. Deciding when an account moves between
 * states (moderation, billing, administration) belongs to other contexts.
 */
enum UserStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Disabled = 'disabled';

    /**
     * Determine whether an account in this state may start a session.
     */
    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }

    /**
     * A human readable label for the state.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Suspended => __('Suspended'),
            self::Disabled => __('Disabled'),
        };
    }

    /**
     * The message shown when an account in this state is denied access.
     */
    public function deniedReason(): string
    {
        return match ($this) {
            self::Active => __('This account is active.'),
            self::Suspended => __('This account has been suspended.'),
            self::Disabled => __('This account has been disabled.'),
        };
    }
}
