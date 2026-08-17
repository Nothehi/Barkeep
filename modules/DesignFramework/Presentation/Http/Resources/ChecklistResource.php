<?php

namespace Modules\DesignFramework\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DesignFramework\Domain\Models\Checklist;

/**
 * One readiness gate, with its requirements when they are loaded.

 * The items come along because a checklist without them is a heading. What does *not* come along
 * is which of them a game has ticked — that is {@see ChecklistProgressResource}, which pairs this
 * list with a particular studio's completions.
 *
 * The phase is sent as an id rather than nested, because a phase page already knows which phase
 * it is and the builder renders the whole version's content grouped by phase from one flat list.
 * A null phase means the content applies across the methodology.
 *
 * @mixin Checklist
 */
class ChecklistResource extends JsonResource
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
            'framework_version_id' => $this->framework_version_id,
            'phase_id' => $this->phase_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'items' => ChecklistItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenCounted('items'),
            'position' => $this->position,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
