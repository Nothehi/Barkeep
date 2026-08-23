<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Domain\Models\PhaseTransition;

/**
 * The representation of one way play can advance.
 *
 * The phase names travel with it because a transition read as two uuids is
 * unreadable, and the condition's whole statement because that is what an arrow
 * in the diagram is labelled with.
 *
 * @mixin PhaseTransition
 */
class PhaseTransitionResource extends JsonResource
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
            'from_phase_id' => $this->from_phase_id,
            'to_phase_id' => $this->to_phase_id,
            'from_phase_name' => $this->fromPhase?->name,
            'to_phase_name' => $this->toPhase?->name,
            'condition_id' => $this->condition_id,
            'condition_statement' => $this->condition?->statement(),
            'trigger_id' => $this->trigger_id,
            'trigger_name' => $this->trigger?->name,
            'is_guarded' => $this->hasGuard(),
            'position' => $this->position,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
