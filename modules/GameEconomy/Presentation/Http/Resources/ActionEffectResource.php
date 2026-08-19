<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameEconomy\Domain\Models\ActionEffect;

/**
 * The representation of something an action does beyond moving resources.
 *
 * `label` is built by the domain — "Maximum hand size +2" — so the sign and the
 * spacing are decided once rather than reassembled by every screen that renders
 * one.
 *
 * @mixin ActionEffect
 */
class ActionEffectResource extends JsonResource
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
            'action_id' => $this->action_id,
            'effect_type' => $this->effect_type->value,
            'effect_type_label' => $this->effect_type->label(),
            'expects_value' => $this->effect_type->expectsValue(),
            'target' => $this->target,
            'value' => $this->value?->label(),
            'label' => $this->label(),
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
