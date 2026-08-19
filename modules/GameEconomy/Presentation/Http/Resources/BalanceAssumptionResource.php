<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameEconomy\Domain\Models\BalanceAssumption;
use Modules\Identity\Presentation\Http\Resources\UserResource;

/**
 * The representation of one belief the numbers were chosen to satisfy.
 *
 * `needs_evidence` is the domain's own reading of a low-confidence assumption,
 * sent so the interface can mark it without keeping a second copy of what "low"
 * implies.
 *
 * @mixin BalanceAssumption
 */
class BalanceAssumptionResource extends JsonResource
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
            'description' => $this->description,
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'confidence' => $this->confidence->value,
            'confidence_label' => $this->confidence->label(),
            'needs_evidence' => $this->needsEvidence(),
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
