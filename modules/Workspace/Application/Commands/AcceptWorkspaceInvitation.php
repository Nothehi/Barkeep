<?php

namespace Modules\Workspace\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\InvitationStatus;
use Modules\Workspace\Domain\Events\WorkspaceInvitationAccepted;
use Modules\Workspace\Domain\Events\WorkspaceMemberAdded;
use Modules\Workspace\Domain\Exceptions\InvitationIsNotAcceptable;
use Modules\Workspace\Domain\Exceptions\MembershipRuleViolation;
use Modules\Workspace\Domain\Exceptions\WorkspaceIsNotActive;
use Modules\Workspace\Domain\Models\WorkspaceInvitation;
use Modules\Workspace\Domain\Models\WorkspaceMember;
use Modules\Workspace\Domain\ValueObjects\InvitationToken;
use SensitiveParameter;

/**
 * Redeem an invitation and join the workspace.
 *
 * Nothing about the target is taken from the caller. The workspace, the role
 * and the address being claimed all come out of the invitation the token
 * resolves to, and the account comes from the session — so a caller cannot
 * point a valid token at a workspace or a role it was not issued for.
 *
 * The invitation row is locked and re-checked inside the transaction, which
 * is what makes the token single use: a second request replaying it finds the
 * row already accepted rather than a stale pending copy.
 */
final class AcceptWorkspaceInvitation
{
    public function handle(User $actor, #[SensitiveParameter] InvitationToken $token): WorkspaceMember
    {
        /** @var array{member: WorkspaceMember, invitation: WorkspaceInvitation} $accepted */
        $accepted = DB::transaction(function () use ($actor, $token): array {
            $invitation = WorkspaceInvitation::query()
                ->forToken($token)
                ->lockForUpdate()
                ->first();

            if ($invitation === null) {
                throw InvitationIsNotAcceptable::notFound();
            }

            if (! $invitation->isAcceptable()) {
                throw InvitationIsNotAcceptable::forStatus($invitation->effectiveStatus());
            }

            if (! $invitation->wasSentTo($actor)) {
                throw InvitationIsNotAcceptable::addressMismatch($invitation->email);
            }

            $workspace = $invitation->workspace;

            if ($workspace === null) {
                throw InvitationIsNotAcceptable::notFound();
            }

            if (! $workspace->isModifiable()) {
                throw WorkspaceIsNotActive::forStatus($workspace->status);
            }

            if ($workspace->hasMember($actor)) {
                throw MembershipRuleViolation::alreadyAMember();
            }

            $member = $workspace->members()->create([
                'user_id' => $actor->id,
                'role' => $invitation->role,
                'joined_at' => now(),
            ]);

            $invitation->forceFill([
                'status' => InvitationStatus::Accepted,
                'accepted_at' => now(),
            ])->save();

            $member->setRelation('workspace', $workspace);

            return ['member' => $member, 'invitation' => $invitation];
        });

        $member = $accepted['member'];

        event(new WorkspaceMemberAdded(
            workspaceId: $member->workspace_id,
            memberId: $member->id,
            userId: $actor->id,
            role: $member->role,
        ));

        event(new WorkspaceInvitationAccepted(
            workspaceId: $member->workspace_id,
            invitationId: $accepted['invitation']->id,
            userId: $actor->id,
        ));

        return $member;
    }
}
