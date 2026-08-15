<?php

namespace Modules\Workspace\Application\Commands;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Application\DTOs\CreateWorkspaceData;
use Modules\Workspace\Application\Services\WorkspaceSlugGenerator;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Events\WorkspaceCreated;
use Modules\Workspace\Domain\Exceptions\InvalidWorkspaceSlug;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Open a new workspace on behalf of the account that asked for it.
 *
 * The workspace and its owner membership are created together. A workspace
 * whose owner is not a member is a state the rest of the module assumes can
 * never happen, so it must not be observable even for an instant — hence the
 * transaction.
 */
final class CreateWorkspace
{
    public function __construct(private readonly WorkspaceSlugGenerator $slugs) {}

    public function handle(User $owner, CreateWorkspaceData $data): Workspace
    {
        $slug = $data->slug ?? $this->slugs->forName($data->name);

        try {
            $workspace = DB::transaction(function () use ($owner, $data, $slug): Workspace {
                $workspace = new Workspace;

                $workspace->fill([
                    'name' => $data->name,
                    'slug' => $slug->value,
                    'description' => $data->description,
                ]);

                /**
                 * Set outside the fill: ownership is not a mass assignable
                 * attribute, precisely so that no request can ever name the
                 * account a workspace belongs to.
                 */
                $workspace->owner_id = $owner->id;

                $workspace->save();

                $workspace->members()->create([
                    'user_id' => $owner->id,
                    'role' => WorkspaceRole::Owner,
                    'joined_at' => now(),
                ]);

                return $workspace;
            });
        } catch (UniqueConstraintViolationException) {
            /**
             * Two people claimed the same address between validation and the
             * insert. The database settled it; report the loser's slug back
             * rather than renaming their workspace behind their back.
             */
            throw InvalidWorkspaceSlug::taken($slug->value);
        }

        event(new WorkspaceCreated(
            workspaceId: $workspace->id,
            ownerId: $owner->id,
            slug: $workspace->slug,
            createdAt: $workspace->created_at ?? now(),
        ));

        return $workspace;
    }
}
