<?php

namespace Modules\Identity\Infrastructure\Authentication;

use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Modules\Identity\Application\Commands\RegisterUser;
use Modules\Identity\Application\DTOs\RegisterUserData;
use Modules\Identity\Application\Validation\PasswordValidationRules;
use Modules\Identity\Application\Validation\ProfileValidationRules;
use Modules\Identity\Domain\Models\User;

/**
 * Adapts Fortify's registration contract onto the Identity application layer.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private readonly RegisterUser $registerUser) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $validated = Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return $this->registerUser->handle(RegisterUserData::fromArray($validated));
    }
}
