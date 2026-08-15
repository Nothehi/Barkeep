<?php

namespace Modules\Identity\Application\Queries;

use Modules\Identity\Domain\Models\User;
use Modules\Identity\Domain\ValueObjects\EmailAddress;

/**
 * Look up an account by its email address.
 *
 * Other bounded contexts address people by email before they have an account
 * — a workspace invitation is the obvious case. This is the contract they use
 * to ask "has this address registered yet?" without knowing how Identity
 * stores or normalises addresses.
 */
final class GetUserByEmail
{
    public function handle(EmailAddress $email): ?User
    {
        return User::query()
            ->where('email', $email->value)
            ->first();
    }
}
