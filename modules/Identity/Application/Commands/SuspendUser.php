<?php

namespace Modules\Identity\Application\Commands;

use Modules\Identity\Domain\Enums\UserStatus;
use Modules\Identity\Domain\Events\UserSuspended;
use Modules\Identity\Domain\Models\User;

/**
 * Suspend an account so that it can no longer authenticate.
 *
 * Identity only enforces the state. Deciding that an account deserves
 * suspension is Moderation's and Administration's business, and those contexts
 * are expected to call this command.
 */
final class SuspendUser
{
    public function handle(User $user): User
    {
        if ($user->status === UserStatus::Suspended) {
            return $user;
        }

        $user->forceFill(['status' => UserStatus::Suspended])->save();

        event(new UserSuspended(userId: $user->id, suspendedAt: now()));

        return $user;
    }
}
