<?php

namespace Modules\Workspace\Application\Commands;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Events\WorkspaceMemberAdded;
use Modules\Workspace\Domain\Exceptions\MembershipRuleViolation;
use Modules\Workspace\Domain\Exceptions\WorkspaceIsNotActive;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceMember;

/**
 * Give an existing account membership of a workspace directly.
 *
 * The counterpart to being invited: used when somebody with an account is
 * added rather than emailed a link.
 *
 * Redeeming an invitation deliberately does *not* route through here, even
 * though it creates the same row. Acceptance has to create the membership and
 * mark the invitation redeemed in one transaction — otherwise a replayed
 * token could be redeemed twice — and that transaction belongs to
 * {@see AcceptWorkspaceInvitation}. The invariants both paths depend on live
 * where they cannot be bypassed either way: the unique index on
 * (workspace_id, user_id), and {@see WorkspaceRole::isAssignable()}.
 */
final class AddWorkspaceMember
{
    public function handle(
        Workspace $workspace,
        User $user,
        WorkspaceRole $role = WorkspaceRole::Member,
    ): WorkspaceMember {
        if (! $workspace->isModifiable()) {
            throw WorkspaceIsNotActive::forStatus($workspace->status);
        }

        if (! $role->isAssignable()) {
            throw MembershipRuleViolation::cannotGrantOwnership();
        }

        try {
            $member = $workspace->members()->create([
                'user_id' => $user->id,
                'role' => $role,
                'joined_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            /**
             * Two administrators added the same person at once, or an
             * invitation was redeemed twice. The unique index settled it, and
             * the outcome is the same either way: they are a member.
             */
            throw MembershipRuleViolation::alreadyAMember();
        }

        event(new WorkspaceMemberAdded(
            workspaceId: $workspace->id,
            memberId: $member->id,
            userId: $user->id,
            role: $role,
        ));

        return $member;
    }
}
