<?php

namespace Modules\DesignFramework\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DesignFramework\Domain\Models\ChecklistItemCompletion;
use Modules\Identity\Presentation\Http\Resources\UserResource;

/**
 * A record that a game met one checklist requirement.
 *
 * Returned from the endpoint that ticks a box, so the client can confirm what was written. The
 * screens themselves read {@see ChecklistProgressResource} instead, because a checklist is drawn
 * as a list with ticks rather than as a list of completions.
 *
 * @mixin ChecklistItemCompletion
 */
class ChecklistItemCompletionResource extends JsonResource
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
            'game_framework_id' => $this->game_framework_id,
            'checklist_item_id' => $this->checklist_item_id,
            'item' => ChecklistItemResource::make($this->whenLoaded('item')),
            'notes' => $this->notes,
            'completed_by' => $this->completed_by,
            'completer' => UserResource::make($this->whenLoaded('completer')),
            'completed_at' => $this->completed_at->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
