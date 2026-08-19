<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameEconomy\Domain\Models\ScenarioVariable;

/**
 * The representation of one value a scenario states differently.
 *
 * The base value travels beside the override, because an override on its own
 * says nothing: "15" is only a scenario when you can see that the profile says
 * 10. The delta is computed by the domain for the same reason the range check
 * is — subtracting two decimal strings in TypeScript would mean parsing them
 * into floats.
 *
 * @mixin ScenarioVariable
 */
class ScenarioVariableResource extends JsonResource
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
            'scenario_id' => $this->scenario_id,
            'balance_variable_id' => $this->balance_variable_id,
            'variable_name' => $this->variable?->name,
            'variable_slug' => $this->variable?->slug,
            'unit' => $this->variable?->unit,
            'base_value' => $this->variable?->value->label(),
            'value' => $this->value->label(),
            'delta' => $this->delta()?->label(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
