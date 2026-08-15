<?php

namespace Modules\Workspace\Domain\Exceptions;

use Modules\Workspace\Domain\Enums\InvitationStatus;

/**
 * Raised when an invitation cannot be used to join a workspace.
 *
 * The message never distinguishes "no such token" from "wrong token", so a
 * caller holding a guessed token learns nothing from the response.
 */
final class InvitationIsNotAcceptable extends WorkspaceRuleViolation
{
    private function __construct(string $message, private readonly int $status)
    {
        parent::__construct($message);
    }

    public static function forStatus(InvitationStatus $status): self
    {
        return new self($status->deniedReason(), 409);
    }

    public static function notFound(): self
    {
        return new self(__('This invitation link is no longer valid.'), 404);
    }

    /**
     * The signed in account does not own the invited address.
     *
     * Reported as a conflict rather than a denial: the caller is properly
     * authenticated, they are simply the wrong person for this invitation.
     */
    public static function addressMismatch(string $email): self
    {
        return new self(
            __('This invitation was sent to :email. Sign in with that address to accept it.', ['email' => $email]),
            409,
        );
    }

    public function status(): int
    {
        return $this->status;
    }
}
