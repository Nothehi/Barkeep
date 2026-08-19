<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameEconomy\Domain\Models\ActionReward;

/**
 * The representation of one resource an action pays out.
 *
 * @mixin ActionReward
 */
class ActionRewardResource extends JsonResource
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
        $reward = $this->resource;

        return [
            'id' => $this->id,
            'action_id' => $this->action_id,
            'resource_type_id' => $this->resource_type_id,
            'resource_name' => $reward->resource?->name,
            'resource_slug' => $reward->resource?->slug,
            'unit' => $reward->resource?->unit,
            'amount' => $this->amount->label(),
            'is_variable' => $this->is_variable,
            'min_amount' => $this->min_amount?->label(),
            'max_amount' => $this->max_amount?->label(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
