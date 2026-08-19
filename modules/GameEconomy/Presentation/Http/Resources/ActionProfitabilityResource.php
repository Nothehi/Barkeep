<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameEconomy\Domain\ValueObjects\ActionProfitability;
use Modules\GameEconomy\Domain\ValueObjects\ResourceDelta;

/**
 * The representation of what an action does to a player's holdings.
 *
 * One line per resource, and no total anywhere. That absence is the point:
 * "Build costs 5 wood and 2 stone and pays nothing" is an answer, where "Build
 * is worth -7" is a fiction that required deciding wood and stone are
 * interchangeable. Section 26 of the brief refuses to invent that number, and
 * the shape of this payload is what stops an interface from inventing it either.
 *
 * @mixin ActionProfitability
 */
class ActionProfitabilityResource extends JsonResource
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
            'deltas' => array_map($this->renderDelta(...), $this->deltas),
            'effect_count' => $this->effectCount,
            'has_cost' => $this->hasCost(),
            'has_reward' => $this->hasReward(),
            'has_outcome' => $this->hasOutcome(),
            'multiplied_resources' => array_map($this->renderDelta(...), $this->multipliedResources()),
        ];
    }

    /**
     * Render one resource's line.
     *
     * @return array<string, mixed>
     */
    private function renderDelta(ResourceDelta $delta): array
    {
        return [
            'resource_id' => $delta->resourceId,
            'resource_name' => $delta->resourceName,
            'unit' => $delta->unit,
            'cost' => $delta->cost->label(),
            'reward' => $delta->reward->label(),
            'net' => $delta->net()->label(),
            'is_gain' => $delta->isGain(),
            'is_spend' => $delta->isSpend(),
        ];
    }
}
