<?php

namespace Modules\Identity\Infrastructure\Authentication\Listeners;

use Illuminate\Auth\Events\Verified;
use Modules\Identity\Domain\Events\UserEmailVerified;
use Modules\Identity\Domain\Models\User;

/**
 * Translates the framework's email verification event into an Identity domain
 * event, so consumers do not have to listen to framework internals.
 */
class AnnounceEmailVerified
{
    public function handle(Verified $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        event(new UserEmailVerified(
            userId: $event->user->id,
            email: $event->user->email,
            verifiedAt: $event->user->email_verified_at ?? now(),
        ));
    }
}
