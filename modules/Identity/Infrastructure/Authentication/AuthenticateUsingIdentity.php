<?php

namespace Modules\Identity\Infrastructure\Authentication;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Modules\Identity\Application\Commands\AuthenticateUser;
use Modules\Identity\Domain\Exceptions\AccountIsNotActive;
use Modules\Identity\Domain\Exceptions\InvalidEmailAddress;
use Modules\Identity\Domain\Models\User;
use Modules\Identity\Domain\ValueObjects\EmailAddress;

/**
 * The callback Fortify uses to validate login credentials.
 *
 * Returning null lets Fortify report its own generic "these credentials do not
 * match" failure, which keeps invalid addresses indistinguishable from wrong
 * passwords. A blocked account is reported separately, because the person has
 * already proven they own the account.
 */
class AuthenticateUsingIdentity
{
    public function __construct(private readonly AuthenticateUser $authenticateUser) {}

    /**
     * @throws ValidationException when the account may not authenticate.
     */
    public function __invoke(Request $request): ?User
    {
        try {
            return $this->authenticateUser->handle(
                EmailAddress::fromString($request->string(Fortify::username())->toString()),
                (string) $request->input('password'),
            );
        } catch (InvalidEmailAddress) {
            return null;
        } catch (AccountIsNotActive $exception) {
            throw ValidationException::withMessages([
                Fortify::username() => [$exception->getMessage()],
            ]);
        }
    }
}
