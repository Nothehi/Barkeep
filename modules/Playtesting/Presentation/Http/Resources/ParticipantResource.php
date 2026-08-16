<?php

namespace Modules\Playtesting\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Identity\Presentation\Http\Resources\UserResource;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;

/**
 * The representation of somebody who was at a session.
 *
 * `display_name` is always present and is what a screen should show. The
 * account is rendered through Identity's own resource when there is one, and
 * most of the time there is not — which is why the name is the field the
 * interface reads rather than a fallback for a missing user.
 *
 * The account link is restricted to people who share the workspace, so
 * anything published here about them is something the caller could already see
 * on the members screen. That check happens when the participant is added; it
 * is worth stating here too, because this resource is the reason it exists.
 *
 * @mixin PlaytestParticipant
 */
class ParticipantResource extends JsonResource
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
            'session_id' => $this->session_id,
            'user_id' => $this->user_id,
            'user' => UserResource::make($this->whenLoaded('user')),
            'display_name' => $this->display_name,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'is_registered' => $this->isRegistered(),
            'joined_at' => $this->joined_at?->toIso8601String(),
            'left_at' => $this->left_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
