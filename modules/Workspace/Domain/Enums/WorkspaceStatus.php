<?php

namespace Modules\Workspace\Domain\Enums;

/**
 * The lifecycle state of a workspace.
 *
 * A workspace is never destroyed as part of normal lifecycle management:
 * games, playtests and content will hang off it, so it is archived instead.
 */
enum WorkspaceStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
    case Suspended = 'suspended';

    /**
     * Determine whether members may still read the workspace.
     *
     * An archived workspace stays readable so its history does not disappear;
     * a suspended one is closed until an administrator lifts the suspension.
     */
    public function isReadable(): bool
    {
        return $this !== self::Suspended;
    }

    /**
     * Determine whether the workspace and everything inside it may change.
     */
    public function allowsModification(): bool
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
            self::Archived => __('Archived'),
            self::Suspended => __('Suspended'),
        };
    }

    /**
     * The message shown when an action is denied because of this state.
     */
    public function deniedReason(): string
    {
        return match ($this) {
            self::Active => __('This workspace is active.'),
            self::Archived => __('This workspace has been archived.'),
            self::Suspended => __('This workspace has been suspended.'),
        };
    }
}
