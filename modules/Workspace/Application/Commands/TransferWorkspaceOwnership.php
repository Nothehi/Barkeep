<?php

namespace Modules\Workspace\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Events\WorkspaceOwnershipTransferred;
use Modules\Workspace\Domain\Exceptions\MembershipRuleViolation;
use Modules\Workspace\Domain\Exceptions\WorkspaceIsNotActive;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceMember;

/**
 * Hand a workspace to one of its members.
 *
 * Ownership is recorded twice — as `workspaces.owner_id` and as a membership
 * carrying the owner role — and the whole module trusts those two to agree.
 * Doing this as an ordinary role update would mean passing through a state
 * with two owners or none, so all three writes happen under one lock:
 *
 *     old owner  ->  admin (or member)
 *     new owner  ->  owner
 *     workspace.owner_id -> new owner
 *
 * The workspace row is locked first, which also serialises two concurrent
 * transfers: the second one re-reads an owner that has already moved and is
 * rejected rather than overwriting the first.
 */
final class TransferWorkspaceOwnership
{
    public function handle(
        User $actor,
        Workspace $workspace,
        WorkspaceMember $newOwner,
        WorkspaceRole $previousOwnerRole = WorkspaceRole::Admin,
    ): Workspace {
        if (! $previousOwnerRole->isAssignable()) {
            throw MembershipRuleViolation::cannotGrantOwnership();
        }

        $previousOwnerId = DB::transaction(function () use ($workspace, $newOwner, $previousOwnerRole): string {
            $locked = Workspace::query()
                ->whereKey($workspace->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isModifiable()) {
                throw WorkspaceIsNotActive::forStatus($locked->status);
            }

            $incoming = $locked->members()
                ->whereKey($newOwner->getKey())
                ->lockForUpdate()
                ->first();

            if ($incoming === null) {
                throw MembershipRuleViolation::notAMember();
            }

            if ($incoming->user_id === $locked->owner_id) {
                throw MembershipRuleViolation::alreadyTheOwner();
            }

            $previousOwnerId = $locked->owner_id;

            $outgoing = $locked->members()
                ->where('user_id', $previousOwnerId)
                ->lockForUpdate()
                ->first();

            /**
             * The outgoing owner should always have a membership. If a repair
             * ever leaves one behind, transferring is still the right way out
             * of that state, so the demotion is skipped rather than fatal.
             */
            $outgoing?->forceFill(['role' => $previousOwnerRole])->save();

            $incoming->forceFill(['role' => WorkspaceRole::Owner])->save();

            $locked->forceFill(['owner_id' => $incoming->user_id])->save();

            $workspace->setRawAttributes($locked->getAttributes(), sync: true);
            $workspace->forgetResolvedMemberships();
            $newOwner->setRawAttributes($incoming->getAttributes(), sync: true);

            return $previousOwnerId;
        });

        event(new WorkspaceOwnershipTransferred(
            workspaceId: $workspace->id,
            previousOwnerId: $previousOwnerId,
            newOwnerId: $newOwner->user_id,
            previousOwnerRole: $previousOwnerRole,
        ));

        return $workspace;
    }
}
