<?php

namespace Modules\GameDesign\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\Identity\Presentation\Http\Resources\UserResource;

/**
 * The representation of one iteration of a game.
 *
 * The creator is rendered through Identity's own resource rather than as a bare id, because a
 * version list is a history and a history with account ids in it is unusable.
 * It is only rendered when the relation was loaded, so a caller that does not
 * need it does not pay for it.
 *
 * @mixin GameVersion
 */
class GameVersionResource extends JsonResource
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
            'game_id' => $this->game_id,
            'version_number' => $this->version_number,
            'label' => $this->label(),
            'name' => $this->name,
            'description' => $this->description,
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
