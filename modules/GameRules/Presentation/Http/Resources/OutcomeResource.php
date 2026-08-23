<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Domain\Models\DefeatCondition;
use Modules\GameRules\Domain\Models\GameEndCondition;
use Modules\GameRules\Domain\Models\VictoryCondition;

/**
 * The shape a victory, defeat or end condition is sent in.
 *
 * The three records stay separate models because winning, losing and stopping are
 * three different questions a game answers at once. Their *payloads* are
 * identical, so the shaping lives here once and the three named resources below
 * it are subclasses that exist to be named — section 41 of the brief asks for
 * three, and having three with one implementation is better than three copies of
 * the same eight lines.
 *
 * `is_measurable` is sent rather than inferred from `condition_id` being null, so
 * the interface renders "not yet measurable" from the server's own reading rather
 * than from its own.
 *
 * @mixin VictoryCondition|DefeatCondition|GameEndCondition
 */
class OutcomeResource extends JsonResource
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
            'condition_id' => $this->condition_id,
            'condition_statement' => $this->condition?->statement(),
            'is_measurable' => $this->isMeasurable(),
            'priority' => $this->priority,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
