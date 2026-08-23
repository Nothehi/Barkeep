<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Domain\ValueObjects\RuleSetSummary;

/**
 * How much of a rule system there is, counted.
 *
 * The row across the top of the dashboard. `is_empty` and `has_errors` are sent
 * rather than derived, so the interface chooses between the empty state and the
 * real one — and decides whether to offer "Activate" — from the server's own
 * reading rather than by adding numbers up itself.
 *
 * @mixin RuleSetSummary
 */
class RuleSetSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rules' => $this->rules,
            'mechanics' => $this->mechanics,
            'phases' => $this->phases,
            'transitions' => $this->transitions,
            'actions' => $this->actions,
            'requirements' => $this->requirements,
            'conditions' => $this->conditions,
            'condition_groups' => $this->conditionGroups,
            'effects' => $this->effects,
            'triggers' => $this->triggers,
            'victory_conditions' => $this->victoryConditions,
            'defeat_conditions' => $this->defeatConditions,
            'end_conditions' => $this->endConditions,
            'references' => $this->references,
            'warnings' => $this->warnings,
            'errors' => $this->errors,
            'is_empty' => $this->isEmpty(),
            'has_errors' => $this->hasErrors(),
        ];
    }
}
