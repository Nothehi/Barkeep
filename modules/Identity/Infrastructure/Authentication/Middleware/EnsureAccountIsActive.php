<?php

namespace Modules\Identity\Infrastructure\Authentication\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Identity\Application\Commands\LogoutUser;
use Modules\Identity\Domain\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces account state on every authenticated request.
 *
 * Blocking at login is not enough on its own: sessions and remember-me cookies
 * outlive the moment an account is suspended, and passkey logins never touch
 * the password pipeline.
 */
class EnsureAccountIsActive
{
    public function __construct(private readonly LogoutUser $logoutUser) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->canAuthenticate()) {
            $reason = $user->status->deniedReason();

            $this->logoutUser->handle($request->session());

            if ($request->expectsJson()) {
                abort(403, $reason);
            }

            return redirect()->route('login')->withErrors(['email' => $reason]);
        }

        return $next($request);
    }
}
