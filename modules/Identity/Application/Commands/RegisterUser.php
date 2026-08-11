<?php

namespace Modules\Identity\Application\Commands;

use Modules\Identity\Application\DTOs\RegisterUserData;
use Modules\Identity\Domain\Events\UserRegistered;
use Modules\Identity\Domain\Models\User;

/**
 * Create a new account.
 *
 * Registration deliberately creates nothing but the account itself. Personal
 * workspaces are the Workspace context's responsibility and are expected to be
 * created by a listener on {@see UserRegistered}.
 */
final class RegisterUser
{
    public function handle(RegisterUserData $data): User
    {
        $user = User::create([
            'name' => $data->name,
            'email' => $data->email->value,
            'password' => $data->plainPassword,
        ]);

        event(new UserRegistered(
            userId: $user->id,
            name: $user->name,
            email: $user->email,
            registeredAt: $user->created_at ?? now(),
        ));

        return $user;
    }
}
