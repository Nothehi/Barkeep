<?php

namespace Modules\Identity\Domain\Events;

use Carbon\CarbonImmutable;

/**
 * Dispatched once an account has confirmed ownership of its email address.
 *
 * Carries only the identity information consumers need so that other bounded
 * contexts never have to reach into Identity's models.
 */
final readonly class UserEmailVerified
{
    public function __construct(
        public string $userId,
        public string $email,
        public CarbonImmutable $verifiedAt,
    ) {}
}
