<?php

namespace Modules\Identity\Application\Commands;

use Modules\Identity\Domain\Enums\UserStatus;
use Modules\Identity\Domain\Events\UserActivated;
use Modules\Identity\Domain\Models\User;

/**
 * Return an account to the active state so that it can authenticate again.
 */
final class ActivateUser
{
    public function handle(User $user): User
    {
        if ($user->status === UserStatus::Active) {
            return $user;
        }

        $user->forceFill(['status' => UserStatus::Active])->save();

        event(new UserActivated(userId: $user->id, activatedAt: now()));

        return $user;
    }
}
