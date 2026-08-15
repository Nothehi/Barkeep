<?php

namespace Modules\Workspace\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Infrastructure\Authorization\WorkspacePermissions;

/**
 * The representation of a workspace.
 *
 * Members are deliberately absent: a workspace can have hundreds, and every
 * screen that needs them has its own endpoint. What is included instead is
 * what the caller may *do* here, because the client cannot work that out on
 * its own without duplicating the policy.
 *
 * @mixin Workspace
 */
class WorkspaceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status->value,
            'owner_id' => $this->owner_id,
            'members_count' => $this->whenCounted('members'),
            'archived_at' => $this->archived_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'permissions' => $this->permissionsFor($request),
        ];
    }

    /**
     * Resolve what the caller may do with this workspace.
     *
     * @return array<string, bool>
     */
    private function permissionsFor(Request $request): array
    {
        $user = $request->user();

        return $user instanceof User
            ? app(WorkspacePermissions::class)->for($user, $this->resource)
            : WorkspacePermissions::none();
    }
}
