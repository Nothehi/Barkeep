<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\Identity\Presentation\Http\Resources\UserResource;

/**
 * The representation of one hypothetical.
 *
 * @mixin BalanceScenario
 */
class BalanceScenarioResource extends JsonResource
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
            'balance_profile_id' => $this->balance_profile_id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_modifiable' => $this->isModifiable(),
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'overrides' => ScenarioVariableResource::collection($this->whenLoaded('overrides')),
            'overrides_count' => $this->whenCounted('overrides'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
