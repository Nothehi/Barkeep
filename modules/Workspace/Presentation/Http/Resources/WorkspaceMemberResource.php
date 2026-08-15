<?php

namespace Modules\Workspace\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Identity\Presentation\Http\Resources\UserResource;
use Modules\Workspace\Domain\Models\WorkspaceMember;

/**
 * The representation of one person's place in a workspace.
 *
 * The account is rendered through Identity's own resource rather than by
 * reaching for columns, so Workspace never becomes a second opinion on what
 * is safe to publish about a user.
 *
 * @mixin WorkspaceMember
 */
class WorkspaceMemberResource extends JsonResource
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
            'workspace_id' => $this->workspace_id,
            'user_id' => $this->user_id,
            'role' => $this->role->value,
            'joined_at' => $this->joined_at->toIso8601String(),
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
