<?php

namespace Modules\Workspace\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Workspace\Domain\Models\WorkspaceInvitation;

/**
 * An invitation as seen by whoever is holding the link.
 *
 * This is the one representation shown to someone who is not a member — and
 * possibly not signed in at all — so it is cut down to what the landing page
 * has to say: which workspace, as what, and whether the link still works.
 *
 * The invited address is included because the recipient already knows it and
 * needs to be told which account to sign in with. Nothing else about the
 * workspace or its people is exposed.
 *
 * @mixin WorkspaceInvitation
 */
class PublicWorkspaceInvitationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'email' => $this->email,
            'role' => $this->role->value,
            'status' => $this->effectiveStatus()->value,
            'expires_at' => $this->expires_at->toIso8601String(),
            'workspace' => [
                'name' => $this->workspace?->name,
                'slug' => $this->workspace?->slug,
            ],
        ];
    }
}
