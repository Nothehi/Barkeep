<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Domain\Models\RuleMechanic;

/**
 * The representation of one mechanism a rule system uses.
 *
 * Not GameDesign's `MechanicResource`, which represents an entry in the shared,
 * seeded vocabulary of design terms and translates its name on the way out. This
 * one is a studio's own word for a mechanism in one game, and passes through
 * untranslated for the same reason a rule's name does.
 *
 * @mixin RuleMechanic
 */
class MechanicResource extends JsonResource
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
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'position' => $this->position,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
