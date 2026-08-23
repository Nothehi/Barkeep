<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Domain\Models\RuleRequirement;

/**
 * The representation of one gate on a rule or an action.
 *
 * @mixin RuleRequirement
 */
class RuleRequirementResource extends JsonResource
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
            'rule_id' => $this->rule_id,
            'action_id' => $this->action_id,
            'requirement_type' => $this->requirement_type->value,
            'requirement_type_label' => $this->requirement_type->label(),
            'description' => $this->description,
            'value' => $this->value,
            'economy_resource_slug' => $this->economy_resource_slug,
            'position' => $this->position,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
