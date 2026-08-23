<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Domain\Models\RuleEffect;

/**
 * The representation of one thing that happens when a rule or action resolves.
 *
 * The value is a string on the way out as it is on the way in. Nothing computes
 * with it, here or anywhere — see section 33 of the module brief.
 *
 * @mixin RuleEffect
 */
class RuleEffectResource extends JsonResource
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
            'effect_type' => $this->effect_type->value,
            'effect_type_label' => $this->effect_type->label(),
            'target' => $this->target,
            'value' => $this->value,
            'description' => $this->description,
            'economy_resource_slug' => $this->economy_resource_slug,
            'moves_play' => $this->movesPlay(),
            'position' => $this->position,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
