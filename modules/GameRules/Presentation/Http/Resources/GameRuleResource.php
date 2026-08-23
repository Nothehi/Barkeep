<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Domain\Models\GameRule;

/**
 * The representation of one rule.
 *
 * Children travel with it when they have been loaded and as a count otherwise.
 * That split matters: the rule tree draws the whole hierarchy in one pass from a
 * flat list, while a rule's own page needs everything under it and would
 * otherwise need a second request to draw one screen.
 *
 * `type_label` and `status_label` are worded by the enums, so a taxonomy renamed
 * in the domain reads the new way in the interface without anything in TypeScript
 * changing.
 *
 * @mixin GameRule
 */
class GameRuleResource extends JsonResource
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
            'parent_rule_id' => $this->parent_rule_id,
            'phase_id' => $this->phase_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'rule_type' => $this->rule_type->value,
            'rule_type_label' => $this->rule_type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'position' => $this->position,
            'phase' => GamePhaseResource::make($this->whenLoaded('phase')),
            'children' => GameRuleResource::collection($this->whenLoaded('children')),
            'requirements' => RuleRequirementResource::collection($this->whenLoaded('requirements')),
            'effects' => RuleEffectResource::collection($this->whenLoaded('effects')),
            'references' => RuleReferenceResource::collection($this->whenLoaded('references')),
            'children_count' => $this->whenCounted('children'),
            'requirements_count' => $this->whenCounted('requirements'),
            'effects_count' => $this->whenCounted('effects'),
            'references_count' => $this->whenCounted('references'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
