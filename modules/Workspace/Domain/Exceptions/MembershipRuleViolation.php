<?php

namespace Modules\Workspace\Domain\Exceptions;

/**
 * The membership invariants a workspace guarantees.
 *
 * These are grouped into one class because they share a single rule: a
 * workspace always has exactly one owner, and that owner is always a member.
 * Every named constructor below is one way that rule can be broken.
 */
final class MembershipRuleViolation extends WorkspaceRuleViolation
{
    private function __construct(string $message, private readonly int $status = 422, private readonly ?string $field = null)
    {
        parent::__construct($message);
    }

    public static function alreadyAMember(): self
    {
        return new self(__('This person is already a member of the workspace.'), 409, 'email');
    }

    public static function alreadyInvited(): self
    {
        return new self(__('This person already has a pending invitation to the workspace.'), 409, 'email');
    }

    public static function notAMember(): self
    {
        return new self(__('This person is not a member of the workspace.'), 404);
    }

    public static function cannotRemoveTheOwner(): self
    {
        return new self(__('The workspace owner cannot be removed. Transfer ownership first.'), 409);
    }

    public static function ownerCannotLeave(): self
    {
        return new self(__('The workspace owner cannot leave. Transfer ownership first.'), 409);
    }

    public static function cannotChangeTheOwnerRole(): self
    {
        return new self(__('The owner\'s role can only be changed by transferring ownership.'), 409, 'role');
    }

    public static function cannotGrantOwnership(): self
    {
        return new self(__('Ownership can only be granted by transferring it.'), 422, 'role');
    }

    public static function alreadyTheOwner(): self
    {
        return new self(__('This person already owns the workspace.'), 409, 'member_id');
    }

    public function status(): int
    {
        return $this->status;
    }

    public function field(): ?string
    {
        return $this->field;
    }
}
