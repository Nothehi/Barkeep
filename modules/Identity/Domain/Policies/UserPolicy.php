<?php

namespace Modules\Identity\Domain\Policies;

use Modules\Identity\Domain\Models\User;

/**
 * Identity-level authorization only.
 *
 * Workspace access, game permissions, content publishing and user
 * administration are owned by their own bounded contexts.
 */
final class UserPolicy
{
    /**
     * Determine whether the user can view the given account.
     */
    public function view(User $user, User $account): bool
    {
        return $user->is($account);
    }

    /**
     * Determine whether the user can update the given account's profile.
     */
    public function update(User $user, User $account): bool
    {
        return $user->is($account);
    }

    /**
     * Determine whether the user can delete the given account.
     */
    public function delete(User $user, User $account): bool
    {
        return $user->is($account);
    }
}
