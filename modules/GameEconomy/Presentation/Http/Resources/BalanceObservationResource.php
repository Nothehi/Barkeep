<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameEconomy\Domain\Models\BalanceObservation;
use Modules\Identity\Presentation\Http\Resources\UserResource;

/**
 * The representation of what the studio noticed about the economy.
 *
 * `source_reference` goes out as the plain string it was stored as. This module
 * does not resolve it, so it must not pretend to have — an interface that
 * rendered it as a link to a playtest would be promising something no endpoint
 * here can deliver.
 *
 * @mixin BalanceObservation
 */
class BalanceObservationResource extends JsonResource
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
            'title' => $this->title,
            'observation' => $this->observation,
            'source_type' => $this->source_type->value,
            'source_type_label' => $this->source_type->label(),
            'source_reference' => $this->source_reference,
            'is_empirical' => $this->isEmpirical(),
            'severity' => $this->severity->value,
            'severity_label' => $this->severity->label(),
            'demands_action' => $this->demandsAction(),
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
