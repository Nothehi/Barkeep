<?php

namespace Modules\Identity\Application\Commands;

use Modules\Identity\Application\DTOs\UpdateProfileData;
use Modules\Identity\Domain\Models\User;

/**
 * Update the profile basics an account owns.
 *
 * Changing the email address resets verification, because ownership of the new
 * address has not been proven yet.
 */
final class UpdateUserProfile
{
    public function handle(User $user, UpdateProfileData $data): User
    {
        $user->fill([
            'name' => $data->name,
            'email' => $data->email->value,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user;
    }
}
