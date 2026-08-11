<?php

namespace Modules\Identity\Domain\Events;

use Carbon\CarbonImmutable;

/**
 * Dispatched after an account's password has been reset via the reset flow.
 *
 * Carries only the identity information consumers need so that other bounded
 * contexts never have to reach into Identity's models.
 */
final readonly class PasswordReset
{
    public function __construct(
        public string $userId,
        public string $email,
        public CarbonImmutable $resetAt,
    ) {}
}
