<?php

namespace Modules\GameDesign\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameDesign\Domain\Models\Game;

/**
 * A game as it appears in a list.
 *
 * Smaller than {@see GameResource} on purpose. A games screen renders many at
 * once and needs none of the per-game answers the full resource computes:
 * cards do not offer lifecycle actions, so they need neither the permission
 * map nor the transition list, and resolving both for every game would be
 * work done to be thrown away.
 *
 * What a card does show is here — including the design phase's position in
 * the arc, so progress can be drawn without the client knowing the order of
 * the phases.
 *
 * @mixin Game
 */
class GameSummaryResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'design_phase' => $this->design_phase->value,
            'design_phase_label' => $this->design_phase->label(),
            'design_phase_position' => $this->design_phase->position(),
            'versions_count' => $this->whenCounted('versions'),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
