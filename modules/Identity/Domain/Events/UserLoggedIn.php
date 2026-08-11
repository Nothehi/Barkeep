<?php

namespace Modules\Identity\Domain\Events;

use Carbon\CarbonImmutable;

/**
 * Dispatched after an account successfully starts a session.
 *
 * Carries only the identity information consumers need so that other bounded
 * contexts never have to reach into Identity's models.
 */
final readonly class UserLoggedIn
{
    public function __construct(
        public string $userId,
        public string $email,
        public CarbonImmutable $loggedInAt,
    ) {}
}
