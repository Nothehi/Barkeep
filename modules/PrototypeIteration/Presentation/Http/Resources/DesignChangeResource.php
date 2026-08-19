<?php

namespace Modules\PrototypeIteration\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Identity\Presentation\Http\Resources\UserResource;
use Modules\PrototypeIteration\Domain\Models\DesignChange;

/**
 * The representation of one deliberate modification.
 *
 * Small, because a change is small. The reason is the field the record exists for, so it is
 * always present rather than being loaded on demand — a change list without reasons is a
 * changelog, and the whole argument for storing these is that it is not one.
 *
 * There are no per-change permissions. Whether a change may be edited or removed is a property
 * of the cycle around it — a change has no lifecycle of its own, it either happened or it did
 * not — so the iteration's `canRecordWork` answers for every change on the screen at once.
 * Computing it per row would be the same answer repeated.
 *
 * @mixin DesignChange
 */
class DesignChangeResource extends JsonResource
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
            'iteration_id' => $this->iteration_id,
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'title' => $this->title,
            'description' => $this->description,
            'reason' => $this->reason,
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
