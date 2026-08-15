<?php

namespace Modules\Workspace\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Identity\Presentation\Http\Resources\UserResource;
use Modules\Workspace\Domain\Models\WorkspaceInvitation;

/**
 * An invitation as seen by the administrators of the workspace that issued it.
 *
 * The token has no representation here in any form — not the plaintext, which
 * only ever existed inside the email, and not the digest, which would be
 * enough to confirm a guess. The invitation is addressed by its own id.
 *
 * @mixin WorkspaceInvitation
 */
class WorkspaceInvitationResource extends JsonResource
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
            'email' => $this->email,
            'role' => $this->role->value,
            'status' => $this->effectiveStatus()->value,
            'expires_at' => $this->expires_at->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'invited_by' => UserResource::make($this->whenLoaded('creator')),
        ];
    }
}
