<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Modules\Identity\Infrastructure\Authentication\Middleware\EnsureAccountIsActive;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /**
         * `locale` joins these for the same reason `appearance` is here: the
         * root Blade template has to write `lang` and `dir` on `<html>` before
         * anything is decrypted, and which language somebody reads in is not a
         * secret worth protecting.
         */
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'locale']);

        /**
         * The API is first party only: its sole client is this application's
         * own front end, calling it from the browser over the session it
         * already has. So it runs the stateful middleware rather than a token
         * guard — there is no third-party integration to mint tokens for, and
         * issuing one would create a credential that outlives the session for
         * no benefit. CSRF applies here exactly as it does to the web routes.
         */
        $middleware->api(prepend: [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ValidateCsrfToken::class,
            EnsureAccountIsActive::class,

            /**
             * The API talks to the same browser as the web routes and returns
             * the same worded domain errors, so it has to answer in the same
             * language rather than defaulting everybody to English.
             */
            SetLocale::class,
        ]);

        $middleware->web(append: [
            EnsureAccountIsActive::class,
            SetLocale::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
