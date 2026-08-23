<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Domain\Models\RuleAction;

/**
 * The representation of one thing a player may do.
 *
 * `economy_action_slug` is a handle, and `economy` beside it is whatever the
 * game's balance profile says about it *today* — resolved at render time through
 * the one adapter allowed to read that module, and absent when the studio has not
 * modelled an economy. Nothing here is a stored copy of a cost.
 *
 * @mixin RuleAction
 */
class RuleActionResource extends JsonResource
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
            'phase_id' => $this->phase_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'action_type' => $this->action_type->value,
            'action_type_label' => $this->action_type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'economy_action_slug' => $this->economy_action_slug,
            'position' => $this->position,
            'phase' => GamePhaseResource::make($this->whenLoaded('phase')),
            'requirements' => RuleRequirementResource::collection($this->whenLoaded('requirements')),
            'effects' => RuleEffectResource::collection($this->whenLoaded('effects')),
            'requirements_count' => $this->whenCounted('requirements'),
            'effects_count' => $this->whenCounted('effects'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
