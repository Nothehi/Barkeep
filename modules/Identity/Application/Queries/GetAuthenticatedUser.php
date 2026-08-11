<?php

namespace Modules\Identity\Application\Queries;

use Illuminate\Contracts\Auth\Guard;
use Modules\Identity\Domain\Models\User;

/**
 * Resolve the account behind the current request.
 *
 * This is the contract other bounded contexts should use to ask Identity "who
 * is acting?" rather than reaching for the auth facade themselves.
 */
final class GetAuthenticatedUser
{
    public function __construct(private readonly Guard $guard) {}

    public function handle(): ?User
    {
        $user = $this->guard->user();

        return $user instanceof User ? $user : null;
    }
}
