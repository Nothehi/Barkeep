<?php

namespace Modules\Identity\Application\Commands;

use Illuminate\Support\Str;
use Modules\Identity\Domain\Events\PasswordReset;
use Modules\Identity\Domain\Models\User;

/**
 * Apply a new password chosen through the password reset flow.
 *
 * The reset token itself is issued and consumed by Laravel's password broker;
 * this command only owns what happens to the account afterwards.
 */
final class ResetPassword
{
    public function handle(User $user, string $plainPassword): User
    {
        $user->forceFill([
            'password' => $plainPassword,
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset(
            userId: $user->id,
            email: $user->email,
            resetAt: now(),
        ));

        return $user;
    }
}
