<?php

namespace Modules\Identity\Infrastructure\Authentication;

use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Modules\Identity\Application\Commands\ResetPassword;
use Modules\Identity\Application\Validation\PasswordValidationRules;
use Modules\Identity\Domain\Models\User;

/**
 * Adapts Fortify's password reset contract onto the Identity application layer.
 */
class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function __construct(private readonly ResetPassword $resetPassword) {}

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        $validated = Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $this->resetPassword->handle($user, $validated['password']);
    }
}
