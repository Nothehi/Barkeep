<?php

namespace Modules\Workspace\Application\DTOs;

use Modules\Identity\Domain\ValueObjects\EmailAddress;
use Modules\Workspace\Domain\Enums\WorkspaceRole;

/**
 * The validated input required to invite somebody to a workspace.
 *
 * The address is normalised through Identity's value object, so that an
 * invitation to "Ana@Example.com" is matched against the account that
 * registered as "ana@example.com".
 */
final readonly class InviteMemberData
{
    public function __construct(
        public EmailAddress $email,
        public WorkspaceRole $role = WorkspaceRole::Member,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            email: EmailAddress::fromString((string) $input['email']),
            role: isset($input['role'])
                ? WorkspaceRole::from((string) $input['role'])
                : WorkspaceRole::Member,
        );
    }
}
