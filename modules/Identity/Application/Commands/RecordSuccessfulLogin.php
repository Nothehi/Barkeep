<?php

namespace Modules\Identity\Application\Commands;

use Modules\Identity\Domain\Events\UserLoggedIn;
use Modules\Identity\Domain\Models\User;

/**
 * Record that an account has started a session.
 *
 * Runs for every authentication route — password, two factor and passkey —
 * because it is driven by the framework's login event rather than by a
 * specific controller.
 */
final class RecordSuccessfulLogin
{
    public function handle(User $user): User
    {
        $loggedInAt = now();

        $user->forceFill(['last_login_at' => $loggedInAt])->save();

        event(new UserLoggedIn(
            userId: $user->id,
            email: $user->email,
            loggedInAt: $loggedInAt,
        ));

        return $user;
    }
}
