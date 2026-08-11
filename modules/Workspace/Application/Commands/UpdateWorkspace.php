<?php

namespace Modules\Workspace\Application\Commands;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Application\DTOs\UpdateWorkspaceData;
use Modules\Workspace\Domain\Events\WorkspaceUpdated;
use Modules\Workspace\Domain\Exceptions\InvalidWorkspaceSlug;
use Modules\Workspace\Domain\Exceptions\WorkspaceIsNotActive;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Change a workspace's own settings.
 *
 * Only the workspace's identity is touched here. Membership, roles and
 * ownership each have their own use case, because each has its own rules
 * about who may perform it.
 */
final class UpdateWorkspace
{
    public function handle(User $actor, Workspace $workspace, UpdateWorkspaceData $data): Workspace
    {
        if (! $workspace->isModifiable()) {
            throw WorkspaceIsNotActive::forStatus($workspace->status);
        }

        $workspace->fill([
            'name' => $data->name,
            'slug' => $data->slug->value,
            'description' => $data->description,
        ]);

        $changed = array_keys($workspace->getDirty());

        if ($changed === []) {
            return $workspace;
        }

        try {
            $workspace->save();
        } catch (UniqueConstraintViolationException) {
            throw InvalidWorkspaceSlug::taken($data->slug->value);
        }

        event(new WorkspaceUpdated(
            workspaceId: $workspace->id,
            updatedBy: $actor->id,
            changed: $changed,
        ));

        return $workspace;
    }
}
