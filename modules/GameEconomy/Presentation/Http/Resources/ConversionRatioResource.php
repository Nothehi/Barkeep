<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameEconomy\Domain\ValueObjects\ConversionRatio;

/**
 * The representation of what one resource buys of another.
 *
 * Always tied to the action that produces it, and never composed into an
 * exchange rate for the whole economy — resources do not share a scalar value
 * unless a designer says they do.
 *
 * A null ratio is a real answer rather than a failure: an action that costs
 * nothing converts nothing at any rate.
 *
 * @mixin ConversionRatio
 */
class ConversionRatioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'action_id' => $this->actionId,
            'action_name' => $this->actionName,
            'from_resource_id' => $this->fromResourceId,
            'from_resource_name' => $this->fromResourceName,
            'from_amount' => $this->fromAmount->label(),
            'to_resource_id' => $this->toResourceId,
            'to_resource_name' => $this->toResourceName,
            'to_amount' => $this->toAmount->label(),
            'ratio' => $this->ratio?->label(),
            'is_defined' => $this->isDefined(),
            'label' => $this->label(),
        ];
    }
}
