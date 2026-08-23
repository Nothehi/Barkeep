<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Domain\Models\GamePhase;

/**
 * The representation of one stage of play.
 *
 * `is_entry` and `is_terminal` are sent rather than derived from the type,
 * because the graph and the phase designer both need them and neither should hold
 * a copy of which phase types mean what.
 *
 * @mixin GamePhase
 */
class GamePhaseResource extends JsonResource
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
            'parent_phase_id' => $this->parent_phase_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'phase_type' => $this->phase_type->value,
            'phase_type_label' => $this->phase_type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_entry' => $this->isEntry(),
            'is_terminal' => $this->isTerminal(),
            'position' => $this->position,
            'children' => GamePhaseResource::collection($this->whenLoaded('children')),
            'actions' => RuleActionResource::collection($this->whenLoaded('actions')),
            'children_count' => $this->whenCounted('children'),
            'actions_count' => $this->whenCounted('actions'),
            'rules_count' => $this->whenCounted('rules'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
