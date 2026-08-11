<?php

namespace Modules\Identity\Application\DTOs;

use Modules\Identity\Domain\ValueObjects\EmailAddress;

/**
 * The validated input required to register a new account.
 */
final readonly class RegisterUserData
{
    public function __construct(
        public string $name,
        public EmailAddress $email,
        public string $plainPassword,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            name: (string) $input['name'],
            email: EmailAddress::fromString((string) $input['email']),
            plainPassword: (string) $input['password'],
        );
    }
}
