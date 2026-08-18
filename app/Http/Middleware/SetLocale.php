<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * The language comes from a cookie, so it comes from the client, so it is
     * checked against `config('locales.supported')` before it reaches
     * `App::setLocale()`. Anything unrecognised falls back to the configured
     * default rather than erroring: a stale or hand-edited cookie should give
     * somebody English, not a 500.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        App::setLocale($locale);

        /**
         * The root Blade template writes `lang` and `dir` on `<html>` before
         * any JavaScript runs, so a right-to-left page is laid out correctly
         * on first paint rather than flipping once React mounts.
         */
        View::share('locale', $locale);
        View::share('direction', $this->direction($locale));

        return $next($request);
    }

    /**
     * The language this request should be served in.
     */
    private function resolve(Request $request): string
    {
        $requested = $request->cookie(config('locales.cookie', 'locale'));

        if (is_string($requested) && $this->isSupported($requested)) {
            return $requested;
        }

        return (string) config('app.locale');
    }

    /**
     * Determine whether the application is willing to be asked for a locale.
     */
    private function isSupported(string $locale): bool
    {
        return array_key_exists($locale, (array) config('locales.supported', []));
    }

    /**
     * The writing direction of the given locale.
     */
    private function direction(string $locale): string
    {
        return (string) config("locales.supported.{$locale}.direction", 'ltr');
    }
}
