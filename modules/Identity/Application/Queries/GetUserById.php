<?php

namespace Modules\Identity\Application\Queries;

use Modules\Identity\Domain\Models\User;

/**
 * Look up an account by its identifier.
 *
 * Other bounded contexts store the user id alongside their own aggregates and
 * use this to resolve the account without querying Identity's tables directly.
 */
final class GetUserById
{
    public function handle(string $id): ?User
    {
        return User::query()->find($id);
    }
}
