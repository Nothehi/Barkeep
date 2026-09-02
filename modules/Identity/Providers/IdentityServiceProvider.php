<?php

namespace Modules\Identity\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Verified;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Modules\Identity\Application\Queries\GetAuthenticatedUser;
use Modules\Identity\Domain\Models\User;
use Modules\Identity\Domain\Policies\UserPolicy;
use Modules\Identity\Infrastructure\Authentication\AuthenticateUsingIdentity;
use Modules\Identity\Infrastructure\Authentication\CreateNewUser;
use Modules\Identity\Infrastructure\Authentication\Listeners\AnnounceEmailVerified;
use Modules\Identity\Infrastructure\Authentication\Listeners\AnnounceLogout;
use Modules\Identity\Infrastructure\Authentication\Listeners\RecordLogin;
use Modules\Identity\Infrastructure\Authentication\Middleware\ThrottlePasswordResetRequests;
use Modules\Identity\Infrastructure\Authentication\ResetUserPassword;
use Modules\Identity\Infrastructure\Authentication\Responses\GenericPasswordResetLinkResponse;
use Modules\Identity\Presentation\Http\Resources\AuthenticatedUserResource;

/**
 * Wires the Identity bounded context into the application.
 *
 * Identity owns authentication end to end, so everything Fortify needs is
 * configured here rather than in a general purpose application provider.
 */
class IdentityServiceProvider extends ServiceProvider
{
    /**
     * Register the module's bindings.
     */
    public function register(): void
    {
        $this->app->bind(
            FailedPasswordResetLinkRequestResponse::class,
            GenericPasswordResetLinkResponse::class,
        );
    }

    /**
     * Bootstrap the module.
     */
    public function boot(): void
    {
        $this->configureAuthentication();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configurePolicies();
        $this->configureEventListeners();
        $this->configureSharedData();
    }

    /**
     * Point Fortify at the module's application layer.
     */
    private function configureAuthentication(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::authenticateUsing(
            fn (Request $request) => $this->app->make(AuthenticateUsingIdentity::class)($request),
        );
    }

    /**
     * Configure the Inertia pages backing Fortify's authentication routes.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting for the authentication surface.
     *
     * Password reset link requests are throttled by
     * {@see ThrottlePasswordResetRequests}
     * because Fortify does not expose a named limiter for that route.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('passkeys', function (Request $request) {
            return Limit::perMinute(10)->by(
                ($request->input('credential.id') ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
    }

    /**
     * Register the module's identity level policies.
     */
    private function configurePolicies(): void
    {
        Gate::policy(User::class, UserPolicy::class);
    }

    /**
     * Translate framework authentication events into Identity domain events.
     */
    private function configureEventListeners(): void
    {
        Event::listen(Login::class, RecordLogin::class);
        Event::listen(Logout::class, AnnounceLogout::class);
        Event::listen(Verified::class, AnnounceEmailVerified::class);
    }

    /**
     * Share the authenticated account with every Inertia page.
     *
     * Identity contributes this prop itself so that no other part of the
     * application has to know how an account is represented.
     */
    private function configureSharedData(): void
    {
        Inertia::share('auth', function (Request $request): array {
            $user = $this->app->make(GetAuthenticatedUser::class)->handle();

            return [
                'user' => $user instanceof User
                    ? AuthenticatedUserResource::make($user)->resolve($request)
                    : null,
            ];
        });
    }
}
