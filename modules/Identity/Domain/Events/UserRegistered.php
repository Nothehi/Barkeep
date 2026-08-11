<?php

namespace Modules\Identity\Domain\Events;

use Carbon\CarbonImmutable;

/**
 * Dispatched once a new account has been created.
 *
 * Carries only the identity information consumers need so that other bounded
 * contexts never have to reach into Identity's models.
 */
final readonly class UserRegistered
{
    public function __construct(
        public string $userId,
        public string $name,
        public string $email,
        public CarbonImmutable $registeredAt,
    ) {}
}
