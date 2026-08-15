<?php

namespace Modules\Workspace\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Events\WorkspaceMemberRemoved;
use Modules\Workspace\Domain\Exceptions\MembershipRuleViolation;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Give up membership of a workspace voluntarily.
 *
 * Kept separate from {@see RemoveWorkspaceMember} even though the row it
 * deletes is the same, because who may do it and when differ: anyone may
 * leave, but the owner may not, since a workspace with no owner has nobody
 * left who can archive it or manage its members.
 */
final class LeaveWorkspace
{
    public function handle(User $actor, Workspace $workspace): void
    {
        DB::transaction(function () use ($actor, $workspace): void {
            $member = $workspace->members()
                ->where('user_id', $actor->id)
                ->lockForUpdate()
                ->first();

            if ($member === null) {
                throw MembershipRuleViolation::notAMember();
            }

            if ($member->isOwner()) {
                throw MembershipRuleViolation::ownerCannotLeave();
            }

            $member->delete();
            $workspace->forgetResolvedMemberships();
        });

        event(new WorkspaceMemberRemoved(
            workspaceId: $workspace->id,
            userId: $actor->id,
            removedBy: $actor->id,
        ));
    }
}
