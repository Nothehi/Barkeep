<?php

namespace Modules\Identity\Infrastructure\Authentication\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limits password reset link requests by address and IP.
 *
 * Fortify does not expose a named limiter for this route, and the password
 * broker's own throttle is per-address only, which still lets a single client
 * fan out across many addresses.
 */
class ThrottlePasswordResetRequests
{
    /**
     * The number of reset link requests allowed per decay window.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * The decay window, in seconds.
     */
    private const DECAY_SECONDS = 60;

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('password.email')) {
            return $next($request);
        }

        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.throttle', ['seconds' => RateLimiter::availableIn($key)])],
            ])->status(Response::HTTP_TOO_MANY_REQUESTS);
        }

        RateLimiter::increment($key, self::DECAY_SECONDS);

        return $next($request);
    }

    /**
     * Build the rate limiter key for the given request.
     */
    private function throttleKey(Request $request): string
    {
        return 'identity:password-reset|'.Str::transliterate(
            Str::lower($request->string('email')->toString()).'|'.$request->ip(),
        );
    }
}
