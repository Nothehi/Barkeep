<?php

namespace Modules\Identity\Infrastructure\Authentication\Listeners;

use Illuminate\Auth\Events\Logout;
use Modules\Identity\Domain\Events\UserLoggedOut;
use Modules\Identity\Domain\Models\User;

/**
 * Translates the framework's logout event into an Identity domain event.
 */
class AnnounceLogout
{
    public function handle(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        event(new UserLoggedOut(userId: $event->user->id, loggedOutAt: now()));
    }
}
