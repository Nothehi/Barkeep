<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Domain\Models\RuleReference;

/**
 * The representation of one relationship between two rules.
 *
 * Both rule names travel with it, because an edge read as two uuids tells nobody
 * anything and this is the payload the "what breaks if I change this?" panel is
 * drawn from.
 *
 * @mixin RuleReference
 */
class RuleReferenceResource extends JsonResource
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
            'rule_id' => $this->rule_id,
            'referenced_rule_id' => $this->referenced_rule_id,
            'rule_name' => $this->rule?->name,
            'referenced_rule_name' => $this->referencedRule?->name,
            'reference_type' => $this->reference_type->value,
            'reference_type_label' => $this->reference_type->label(),
            'is_directed' => $this->isDirected(),
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
