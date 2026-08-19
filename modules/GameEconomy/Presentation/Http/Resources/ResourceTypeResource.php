<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameEconomy\Domain\Models\ResourceType;

/**
 * The representation of a single resource.
 *
 * Amounts are published through `label()` rather than as the stored decimal, so
 * a designer who typed 5 reads 5 rather than 5.000000 — the scale is a storage
 * concern and putting it on screen makes an integer economy look like an
 * accounting system.
 *
 * Nulls survive as nulls. "No maximum" and "a maximum of zero" are different
 * statements about a resource, and a resource that flattened them would let the
 * interface invent a limit nobody set.
 *
 * @mixin ResourceType
 */
class ResourceTypeResource extends JsonResource
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
            'balance_profile_id' => $this->balance_profile_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'description' => $this->description,
            'unit' => $this->unit,
            'is_tradeable' => $this->is_tradeable,
            'is_accumulative' => $this->is_accumulative,
            'is_spendable' => $this->is_spendable,
            'is_convertible' => $this->is_convertible,
            'min_value' => $this->min_value?->label(),
            'max_value' => $this->max_value?->label(),
            'starting_value' => $this->starting_value?->label(),
            'position' => $this->position,
            'flows_count' => $this->whenCounted('flows'),
            'costs_count' => $this->whenCounted('costs'),
            'rewards_count' => $this->whenCounted('rewards'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
