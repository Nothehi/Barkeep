<?php

namespace Modules\PrototypeIteration\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Identity\Presentation\Http\Resources\UserResource;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;

/**
 * The representation of one state of a prototype.
 *
 * The creator is rendered through Identity's own resource rather than as a bare id, because a
 * version list is a history and a history with account ids in it is unusable. It is only
 * rendered when the relation was loaded, so a caller that does not need it does not pay for it.
 *
 * `iterations_count` is the field that carries the immutability rule to the interface. A version
 * with any iterations against it is frozen — the module refuses edits to it — and the count is
 * what lets the screen say so, and say how much history is at stake, rather than offering an
 * edit form that will be refused.
 *
 * The prototype is included only when loaded, and it usually is: "v4" on its own is ambiguous
 * in a game with three prototypes on the go, so anywhere a version appears outside its own
 * prototype's screen it needs to name its parent.
 *
 * @mixin PrototypeVersion
 */
class PrototypeVersionResource extends JsonResource
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
            'prototype_id' => $this->prototype_id,
            'version_number' => $this->version_number,
            'label' => $this->label(),
            'name' => $this->name,
            'description' => $this->description,
            'prototype_name' => $this->whenLoaded('prototype', fn (): ?string => $this->prototype?->name),
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'artifacts_count' => $this->whenCounted('artifacts'),
            'iterations_count' => $this->whenCounted('iterations'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
