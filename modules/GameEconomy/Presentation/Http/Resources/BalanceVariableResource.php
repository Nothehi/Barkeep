<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameEconomy\Domain\Models\BalanceVariable;

/**
 * The representation of one tunable number.
 *
 * `is_within_range` is computed by the domain and sent rather than left to the
 * client, so the variable table's warning marker and the analysis agree by
 * construction. Recomputing a comparison in TypeScript would also mean parsing
 * the decimal strings back into floats, which is precisely what this module
 * refuses to do.
 *
 * The names of what the variable is about are flattened in, because the table
 * shows "Wood · Harvest" in a column and a nested object would be destructured
 * to draw it.
 *
 * @mixin BalanceVariable
 */
class BalanceVariableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * The model is pulled into a local first, because `$this->resource` on a
     * JsonResource is the *wrapped model* rather than this record's `resource`
     * relation — the two names collide, and reading through the proxy silently
     * yields null instead of the resource's name.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variable = $this->resource;

        return [
            'id' => $this->id,
            'balance_profile_id' => $this->balance_profile_id,
            'resource_type_id' => $this->resource_type_id,
            'resource_name' => $variable->resource?->name,
            'action_id' => $this->action_id,
            'action_name' => $this->action?->name,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'value' => $this->value->label(),
            'unit' => $this->unit,
            'min_value' => $this->min_value?->label(),
            'max_value' => $this->max_value?->label(),
            'step' => $this->step?->label(),
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'is_probability' => $this->isProbability(),
            'is_within_range' => $this->isWithinItsRange(),
            'overrides_count' => $this->whenCounted('overrides'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
