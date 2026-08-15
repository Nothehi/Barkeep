<?php

namespace Modules\Workspace\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceStatus;
use Modules\Workspace\Domain\Events\WorkspaceArchived;
use Modules\Workspace\Domain\Exceptions\WorkspaceIsNotActive;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Retire a workspace.
 *
 * Nothing is deleted. A workspace is the parent of everything the platform
 * will later hang off it, so ending its life has to leave its games,
 * playtests and history intact and readable.
 */
final class ArchiveWorkspace
{
    public function handle(User $actor, Workspace $workspace): Workspace
    {
        $archivedAt = DB::transaction(function () use ($workspace) {
            $fresh = Workspace::query()
                ->whereKey($workspace->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->isModifiable()) {
                throw WorkspaceIsNotActive::forStatus($fresh->status);
            }

            $archivedAt = now();

            $fresh->forceFill([
                'status' => WorkspaceStatus::Archived,
                'archived_at' => $archivedAt,
            ])->save();

            $workspace->setRawAttributes($fresh->getAttributes(), sync: true);

            return $archivedAt;
        });

        event(new WorkspaceArchived(
            workspaceId: $workspace->id,
            archivedBy: $actor->id,
            archivedAt: $archivedAt,
        ));

        return $workspace;
    }
}
