<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameEconomy\Domain\ValueObjects\ResourceNetFlow;

/**
 * The representation of what enters and leaves for one resource.
 *
 * All three figures travel, not just the net. A resource with 12 in and 8 out
 * and a resource with 2 in and 0 out both net +4 and are completely different
 * games — sending the headline alone would make the interface unable to show the
 * difference.
 *
 * @mixin ResourceNetFlow
 */
class ResourceNetFlowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'resource_id' => $this->resourceId,
            'resource_name' => $this->resourceName,
            'generation' => $this->generation->label(),
            'consumption' => $this->consumption->label(),
            'net' => $this->net()->label(),
            'has_generation' => $this->hasGeneration(),
            'has_consumption' => $this->hasConsumption(),
            'is_surplus' => $this->isSurplus(),
            'is_deficit' => $this->isDeficit(),
            'is_balanced' => $this->isBalanced(),
        ];
    }
}
