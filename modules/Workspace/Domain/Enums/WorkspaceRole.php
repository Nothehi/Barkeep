<?php

namespace Modules\Workspace\Domain\Enums;

/**
 * What a member is allowed to do inside a workspace.
 *
 * The hierarchy is deliberately shallow. Fine grained permissions belong to
 * the contexts that own the resources being protected (games, content,
 * playtests); Workspace only answers "how much of *this workspace* may you
 * administer?".
 */
enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    /**
     * The roles a workspace administrator may hand out.
     *
     * Ownership is never granted this way — it moves through an explicit
     * transfer so the workspace always has exactly one owner.
     *
     * @return list<self>
     */
    public static function assignable(): array
    {
        return [self::Admin, self::Member];
    }

    /**
     * The role's position in the hierarchy, highest first.
     */
    public function level(): int
    {
        return match ($this) {
            self::Owner => 3,
            self::Admin => 2,
            self::Member => 1,
        };
    }

    /**
     * Determine whether this role sits at or above the given one.
     */
    public function atLeast(self $role): bool
    {
        return $this->level() >= $role->level();
    }

    /**
     * Determine whether this role outranks the given one.
     */
    public function outranks(self $role): bool
    {
        return $this->level() > $role->level();
    }

    /**
     * Determine whether this role may be handed out by an administrator.
     */
    public function isAssignable(): bool
    {
        return in_array($this, self::assignable(), strict: true);
    }

    /**
     * A human readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => __('Owner'),
            self::Admin => __('Admin'),
            self::Member => __('Member'),
        };
    }
}
