<?php

namespace Modules\Workspace\Domain\Enums;

use Modules\Workspace\Domain\Models\WorkspaceInvitation;

/**
 * The state of a workspace invitation.
 *
 * Only three states are ever persisted. Expiry is a function of the clock, so
 * it is derived when the invitation is read rather than written by a
 * background job that may not have run yet.
 *
 * @see WorkspaceInvitation::effectiveStatus()
 */
enum InvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Revoked = 'revoked';
    case Expired = 'expired';

    /**
     * Determine whether an invitation in this state may still be accepted.
     */
    public function isAcceptable(): bool
    {
        return $this === self::Pending;
    }

    /**
     * A human readable label for the state.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Accepted => __('Accepted'),
            self::Revoked => __('Revoked'),
            self::Expired => __('Expired'),
        };
    }

    /**
     * The message shown when an invitation in this state cannot be used.
     */
    public function deniedReason(): string
    {
        return match ($this) {
            self::Pending => __('This invitation is still pending.'),
            self::Accepted => __('This invitation has already been accepted.'),
            self::Revoked => __('This invitation has been revoked.'),
            self::Expired => __('This invitation has expired.'),
        };
    }
}
