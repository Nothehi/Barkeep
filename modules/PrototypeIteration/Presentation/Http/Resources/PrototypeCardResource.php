<?php

namespace Modules\PrototypeIteration\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\PrototypeIteration\Domain\Models\Prototype;

/**
 * A prototype as it appears in a list.
 *
 * Smaller than {@see PrototypeResource} on purpose: cards do not offer lifecycle actions, so
 * the server does not compute permissions or transitions for them. A prototypes screen renders
 * many rows and would otherwise ask the gate once per row for answers nothing on the card uses.
 *
 * The design version is flattened to its label rather than nested. A card needs "built from v4"
 * and nothing else about the version; shipping the whole resource per row would be several
 * times the payload for one string.
 *
 * @mixin Prototype
 */
class PrototypeCardResource extends JsonResource
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
            'game_version_id' => $this->game_version_id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'version_label' => $this->whenLoaded('version', fn (): ?string => $this->version?->label()),
            'versions_count' => $this->whenCounted('versions'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
