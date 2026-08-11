<?php

namespace Modules\Identity\Application\Commands;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Session\Session;

/**
 * Terminate the current session.
 *
 * Only the current session is invalidated; revoking every session for an
 * account is a separate use case and is not required yet.
 */
final class LogoutUser
{
    public function __construct(private readonly StatefulGuard $guard) {}

    public function handle(Session $session): void
    {
        $this->guard->logout();

        $session->invalidate();
        $session->regenerateToken();
    }
}
