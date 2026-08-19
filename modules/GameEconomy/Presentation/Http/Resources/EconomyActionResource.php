<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameEconomy\Domain\Models\EconomyAction;

/**
 * The representation of a single action.
 *
 * What the action does travels with it when it has been loaded, and as counts
 * otherwise. That split matters: the actions list draws "3 costs, 1 reward" per
 * row and would otherwise cost three queries per action, while the action page
 * needs every line and would otherwise need three more requests to draw one
 * screen.
 *
 * @mixin EconomyAction
 */
class EconomyActionResource extends JsonResource
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
            'slug' => $this->slug,
            'description' => $this->description,
            'position' => $this->position,
            'costs' => ActionCostResource::collection($this->whenLoaded('costs')),
            'rewards' => ActionRewardResource::collection($this->whenLoaded('rewards')),
            'effects' => ActionEffectResource::collection($this->whenLoaded('effects')),
            'costs_count' => $this->whenCounted('costs'),
            'rewards_count' => $this->whenCounted('rewards'),
            'effects_count' => $this->whenCounted('effects'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
