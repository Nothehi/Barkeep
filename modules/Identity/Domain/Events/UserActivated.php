<?php

namespace Modules\Identity\Domain\Events;

use Carbon\CarbonImmutable;

/**
 * Dispatched when an account returns to the active state.
 *
 * Carries only the identity information consumers need so that other bounded
 * contexts never have to reach into Identity's models.
 */
final readonly class UserActivated
{
    public function __construct(
        public string $userId,
        public CarbonImmutable $activatedAt,
    ) {}
}
