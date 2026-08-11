<?php

namespace Modules\Identity\Domain\Events;

use Carbon\CarbonImmutable;

/**
 * Dispatched after an account's session has been terminated.
 *
 * Carries only the identity information consumers need so that other bounded
 * contexts never have to reach into Identity's models.
 */
final readonly class UserLoggedOut
{
    public function __construct(
        public string $userId,
        public CarbonImmutable $loggedOutAt,
    ) {}
}
