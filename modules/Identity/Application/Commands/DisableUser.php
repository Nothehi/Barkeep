<?php

namespace Modules\Identity\Application\Commands;

use Modules\Identity\Domain\Enums\UserStatus;
use Modules\Identity\Domain\Events\UserDisabled;
use Modules\Identity\Domain\Models\User;

/**
 * Disable an account so that it can no longer authenticate.
 */
final class DisableUser
{
    public function handle(User $user): User
    {
        if ($user->status === UserStatus::Disabled) {
            return $user;
        }

        $user->forceFill(['status' => UserStatus::Disabled])->save();

        event(new UserDisabled(userId: $user->id, disabledAt: now()));

        return $user;
    }
}
