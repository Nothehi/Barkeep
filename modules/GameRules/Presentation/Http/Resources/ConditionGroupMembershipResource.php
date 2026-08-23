<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Domain\Models\ConditionGroupCondition;

/**
 * One condition's place in one group.
 *
 * Sent alongside the conditions rather than instead of them, because removing a
 * condition from a group acts on the *membership*: the same condition may be in
 * several groups, and the client needs the id of this one to detach it without
 * touching the others.
 *
 * @mixin ConditionGroupCondition
 */
class ConditionGroupMembershipResource extends JsonResource
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
            'condition_group_id' => $this->condition_group_id,
            'condition_id' => $this->condition_id,
            'position' => $this->position,
            'condition' => RuleConditionResource::make($this->whenLoaded('condition')),
        ];
    }
}
