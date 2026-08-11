<?php

namespace Modules\Identity\Application\DTOs;

use Modules\Identity\Domain\ValueObjects\EmailAddress;

/**
 * The validated input required to update an account's profile basics.
 */
final readonly class UpdateProfileData
{
    public function __construct(
        public string $name,
        public EmailAddress $email,
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
        );
    }
}
