<?php

namespace Modules\Identity\Infrastructure\Authentication\Listeners;

use Illuminate\Auth\Events\Login;
use Modules\Identity\Application\Commands\RecordSuccessfulLogin;
use Modules\Identity\Domain\Models\User;

/**
 * Bridges the framework's login event onto the Identity application layer so
 * that every authentication route is covered, not just the password form.
 */
class RecordLogin
{
    public function __construct(private readonly RecordSuccessfulLogin $recordSuccessfulLogin) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->recordSuccessfulLogin->handle($event->user);
    }
}
