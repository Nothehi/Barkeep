<?php

namespace Modules\Workspace\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\InvitationStatus;
use Modules\Workspace\Domain\Events\WorkspaceInvitationRevoked;
use Modules\Workspace\Domain\Exceptions\InvitationIsNotAcceptable;
use Modules\Workspace\Domain\Models\WorkspaceInvitation;

/**
 * Withdraw an invitation before it is redeemed.
 *
 * Revoking is a state change rather than a delete, so the record of who was
 * invited and by whom survives, and so the token can never be redeemed again.
 * The row is re-read under a lock because revoking and accepting can race.
 */
final class RevokeWorkspaceInvitation
{
    public function handle(User $actor, WorkspaceInvitation $invitation): WorkspaceInvitation
    {
        DB::transaction(function () use ($invitation): void {
            $locked = WorkspaceInvitation::query()
                ->whereKey($invitation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== InvitationStatus::Pending) {
                throw InvitationIsNotAcceptable::forStatus($locked->status);
            }

            $locked->forceFill([
                'status' => InvitationStatus::Revoked,
                'revoked_at' => now(),
            ])->save();

            $invitation->setRawAttributes($locked->getAttributes(), sync: true);
        });

        event(new WorkspaceInvitationRevoked(
            workspaceId: $invitation->workspace_id,
            invitationId: $invitation->id,
            email: $invitation->email,
            revokedBy: $actor->id,
        ));

        return $invitation;
    }
}
