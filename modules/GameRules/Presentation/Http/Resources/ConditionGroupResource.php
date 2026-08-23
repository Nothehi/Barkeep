<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Domain\Models\ConditionGroup;

/**
 * The representation of one grouping of conditions.
 *
 * Flat, and staying flat. There is no field here for a nested group and
 * deliberately nowhere to put one — see section 19 of the module brief.
 *
 * @mixin ConditionGroup
 */
class ConditionGroupResource extends JsonResource
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
            'rule_set_id' => $this->rule_set_id,
            'name' => $this->name,
            'description' => $this->description,
            'logic_operator' => $this->logic_operator->value,
            'logic_operator_label' => $this->logic_operator->label(),
            'joiner' => $this->logic_operator->joiner(),
            'conditions' => RuleConditionResource::collection($this->whenLoaded('conditions')),
            'memberships' => ConditionGroupMembershipResource::collection($this->whenLoaded('memberships')),
            'conditions_count' => $this->whenCounted('conditions'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
