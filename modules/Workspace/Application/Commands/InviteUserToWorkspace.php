<?php

namespace Modules\Workspace\Application\Commands;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Notification;
use Modules\Identity\Application\Queries\GetUserByEmail;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Application\DTOs\InviteMemberData;
use Modules\Workspace\Domain\Events\WorkspaceInvitationCreated;
use Modules\Workspace\Domain\Exceptions\MembershipRuleViolation;
use Modules\Workspace\Domain\Exceptions\WorkspaceIsNotActive;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceInvitation;
use Modules\Workspace\Domain\ValueObjects\InvitationToken;
use Modules\Workspace\Infrastructure\Notifications\WorkspaceInvitationNotification;

/**
 * Invite an email address to join a workspace.
 *
 * Invitations are addressed to an address, not to an account, because the
 * person may not have registered yet. If they have not, the link takes them
 * through Identity's registration first — Workspace never creates accounts of
 * its own.
 */
final class InviteUserToWorkspace
{
    public function __construct(private readonly GetUserByEmail $users) {}

    public function handle(User $actor, Workspace $workspace, InviteMemberData $data): WorkspaceInvitation
    {
        if (! $workspace->isModifiable()) {
            throw WorkspaceIsNotActive::forStatus($workspace->status);
        }

        if (! $data->role->isAssignable()) {
            throw MembershipRuleViolation::cannotGrantOwnership();
        }

        $existing = $this->users->handle($data->email);

        if ($existing !== null && $workspace->hasMember($existing)) {
            throw MembershipRuleViolation::alreadyAMember();
        }

        $token = InvitationToken::generate();

        $invitation = $workspace->invitations()->make([
            'email' => $data->email->value,
            'role' => $data->role,
            'expires_at' => now()->addDays(WorkspaceInvitation::LIFETIME_IN_DAYS),
            'created_by' => $actor->id,
        ]);

        /**
         * Set outside the fill: the digest is not a mass assignable
         * attribute, so no request can ever supply the secret that redeems an
         * invitation.
         */
        $invitation->token_hash = $token->hash();

        try {
            $invitation->save();
        } catch (UniqueConstraintViolationException) {
            /**
             * A pending invitation for this address already exists — either
             * from a moment ago, or from another administrator at the same
             * time. Reissuing would invalidate the link already in flight.
             */
            throw MembershipRuleViolation::alreadyInvited();
        }

        $invitation->setRelation('workspace', $workspace);

        Notification::route('mail', $data->email->value)->notify(
            new WorkspaceInvitationNotification($invitation, $token, $actor->name),
        );

        event(new WorkspaceInvitationCreated(
            workspaceId: $workspace->id,
            invitationId: $invitation->id,
            email: $invitation->email,
            role: $invitation->role,
            invitedBy: $actor->id,
        ));

        return $invitation;
    }
}
