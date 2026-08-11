<?php

namespace Modules\Identity\Application\Commands;

use Illuminate\Contracts\Auth\StatefulGuard;
use Modules\Identity\Domain\Enums\UserStatus;
use Modules\Identity\Domain\Exceptions\AccountIsNotActive;
use Modules\Identity\Domain\Models\User;
use Modules\Identity\Domain\ValueObjects\EmailAddress;

/**
 * Verify a set of credentials and the account state behind them.
 *
 * This deliberately does not start a session: Fortify's pipeline calls this
 * more than once per login attempt, so it must stay free of side effects.
 * {@see RecordSuccessfulLogin} handles everything that happens after the
 * session actually starts.
 */
final class AuthenticateUser
{
    public function __construct(private readonly StatefulGuard $guard) {}

    /**
     * Resolve the account for the given credentials.
     *
     * Returns null when the credentials do not match, which the caller must
     * report as a generic failure so that valid email addresses cannot be
     * distinguished from invalid ones.
     *
     * @throws AccountIsNotActive when the credentials are valid but the
     *                            account may not authenticate.
     */
    public function handle(EmailAddress $email, string $password): ?User
    {
        $provider = $this->guard->getProvider();
        $credentials = ['email' => $email->value, 'password' => $password];

        $user = $provider->retrieveByCredentials($credentials);

        if (! $user instanceof User || ! $provider->validateCredentials($user, $credentials)) {
            return null;
        }

        if (config('hashing.rehash_on_login', true)) {
            $provider->rehashPasswordIfRequired($user, $credentials);
        }

        if (! $user->canAuthenticate()) {
            throw AccountIsNotActive::forStatus($user->status ?? UserStatus::Active);
        }

        return $user;
    }
}
