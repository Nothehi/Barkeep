<?php

namespace Modules\DesignFramework\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DesignFramework\Domain\Models\ChecklistItem;

/**
 * The representation of one checklist requirement.
 *
 * No completion state. An item is a requirement the framework states, and whether a particular
 * game has met it belongs to that game — {@see ChecklistProgressResource} carries the ticks. A
 * `completed` field here would have to mean "completed by whoever happens to be asking", which
 * is the kind of ambiguity that ends up rendering one studio's progress to another.
 *
 * `required` travels because it changes what the checkbox means: an optional item is a
 * suggestion and does not count towards progress.
 *
 * @mixin ChecklistItem
 */
class ChecklistItemResource extends JsonResource
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
            'checklist_id' => $this->checklist_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'position' => $this->position,
            'required' => $this->required,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
