<?php

namespace Modules\Workspace\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Events\WorkspaceMemberRemoved;
use Modules\Workspace\Domain\Exceptions\MembershipRuleViolation;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceMember;

/**
 * Take somebody's membership of a workspace away.
 *
 * The owner is refused here as well as in the policy. Two requests racing to
 * remove the last owner would each pass their authorization check against a
 * stale read; re-reading the row under a lock is what actually stops it.
 */
final class RemoveWorkspaceMember
{
    public function handle(User $actor, Workspace $workspace, WorkspaceMember $member): void
    {
        $removedUserId = DB::transaction(function () use ($workspace, $member): string {
            $locked = $workspace->members()
                ->whereKey($member->getKey())
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                throw MembershipRuleViolation::notAMember();
            }

            if ($locked->isOwner()) {
                throw MembershipRuleViolation::cannotRemoveTheOwner();
            }

            $locked->delete();
            $workspace->forgetResolvedMemberships();

            return $locked->user_id;
        });

        event(new WorkspaceMemberRemoved(
            workspaceId: $workspace->id,
            userId: $removedUserId,
            removedBy: $actor->id,
        ));
    }
}
