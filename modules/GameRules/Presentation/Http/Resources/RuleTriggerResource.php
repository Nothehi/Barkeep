<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Domain\Models\RuleTrigger;

/**
 * The representation of one thing that happens automatically.
 *
 * Recorded, never fired. Nothing in this payload describes what the trigger would
 * do, because nothing in the module stores it.
 *
 * @mixin RuleTrigger
 */
class RuleTriggerResource extends JsonResource
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
            'trigger_type' => $this->trigger_type->value,
            'trigger_type_label' => $this->trigger_type->label(),
            'position' => $this->position,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
