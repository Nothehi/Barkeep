<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Domain\Models\RuleCondition;

/**
 * The representation of one named logical requirement.
 *
 * `statement` is the three parts read as one sentence, built on the server
 * because two of the three come from enums that word themselves. A client
 * assembling it would be keeping a fourth copy of the vocabulary.
 *
 * `expects_value` travels with it so a condition builder knows not to draw a
 * value box beside "is true".
 *
 * @mixin RuleCondition
 */
class RuleConditionResource extends JsonResource
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
            'condition_type' => $this->condition_type->value,
            'condition_type_label' => $this->condition_type->label(),
            'operator' => $this->operator->value,
            'operator_label' => $this->operator->label(),
            'operator_symbol' => $this->operator->symbol(),
            'expects_value' => $this->operator->expectsValue(),
            'value' => $this->value,
            'statement' => $this->statement(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
