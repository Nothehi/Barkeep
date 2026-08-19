<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameEconomy\Domain\Models\ResourceFlow;

/**
 * The representation of one declared movement of a resource.
 *
 * `amount` is the magnitude the designer typed and `signed_amount` is what it
 * does to the total in play. Both travel, because the interface needs the first
 * to render an editor and the second to draw a direction — and computing the
 * second on the client would put the flow-type table in TypeScript as well as in
 * the domain.
 *
 * @mixin ResourceFlow
 */
class ResourceFlowResource extends JsonResource
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
        $flow = $this->resource;

        return [
            'id' => $this->id,
            'balance_profile_id' => $this->balance_profile_id,
            'resource_type_id' => $this->resource_type_id,
            'resource_name' => $this->whenLoaded('resource', fn (): ?string => $flow->resource?->name),
            'resource_slug' => $this->whenLoaded('resource', fn (): ?string => $flow->resource?->slug),
            'name' => $this->name,
            'description' => $this->description,
            'flow_type' => $this->flow_type->value,
            'flow_type_label' => $this->flow_type->label(),
            'direction' => $this->flow_type->direction(),
            'amount' => $this->amount->label(),
            'signed_amount' => $this->signedAmount()->label(),
            'condition' => $this->condition,
            'position' => $this->position,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
