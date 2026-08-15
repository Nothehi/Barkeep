<?php

namespace Modules\Workspace\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Events\WorkspaceMemberRoleChanged;
use Modules\Workspace\Domain\Exceptions\MembershipRuleViolation;
use Modules\Workspace\Domain\Exceptions\WorkspaceIsNotActive;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceMember;

/**
 * Promote or demote a member.
 *
 * Ownership deliberately cannot move through this command in either
 * direction. Granting it here would produce two owners; taking it away would
 * produce none. Both are handled by
 * {@see TransferWorkspaceOwnership}, which does the two halves together.
 */
final class ChangeWorkspaceMemberRole
{
    public function handle(
        User $actor,
        Workspace $workspace,
        WorkspaceMember $member,
        WorkspaceRole $role,
    ): WorkspaceMember {
        if (! $workspace->isModifiable()) {
            throw WorkspaceIsNotActive::forStatus($workspace->status);
        }

        if (! $role->isAssignable()) {
            throw MembershipRuleViolation::cannotGrantOwnership();
        }

        $previous = DB::transaction(function () use ($workspace, $member, $role): ?WorkspaceRole {
            $locked = $workspace->members()
                ->whereKey($member->getKey())
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                throw MembershipRuleViolation::notAMember();
            }

            if ($locked->isOwner()) {
                throw MembershipRuleViolation::cannotChangeTheOwnerRole();
            }

            if ($locked->role === $role) {
                return null;
            }

            $previous = $locked->role;

            $locked->forceFill(['role' => $role])->save();

            $member->setRawAttributes($locked->getAttributes(), sync: true);
            $workspace->forgetResolvedMemberships();

            return $previous;
        });

        if ($previous !== null) {
            event(new WorkspaceMemberRoleChanged(
                workspaceId: $workspace->id,
                userId: $member->user_id,
                from: $previous,
                to: $role,
                changedBy: $actor->id,
            ));
        }

        return $member;
    }
}
