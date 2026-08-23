<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Application\DTOs\RuleSetAnalysis;

/**
 * Everything the rules dashboard draws, in one payload.
 *
 * One response rather than a dozen, because the sections are not independent: the
 * findings are *about* the rules, the phases and the actions, and a page that
 * fetched them separately would spend part of its life showing errors about a rule
 * set it had not finished receiving.
 *
 * Errors and warnings arrive as two lists rather than one sorted list with a
 * severity field, because the screen draws them under two headings and the counts
 * beside them come from the same object — so the summary and the lists can never
 * disagree.
 *
 * Static, in the sense section 31 of the brief means. Nothing in here was
 * executed, simulated or played.
 *
 * @mixin RuleSetAnalysis
 */
class RuleSetAnalysisResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'summary' => RuleSetSummaryResource::make($this->summary),
            'rules' => GameRuleResource::collection($this->rules),
            'mechanics' => MechanicResource::collection($this->mechanics),
            'phases' => GamePhaseResource::collection($this->phases),
            'transitions' => PhaseTransitionResource::collection($this->transitions),
            'actions' => RuleActionResource::collection($this->actions),
            'requirements' => RuleRequirementResource::collection($this->requirements),
            'conditions' => RuleConditionResource::collection($this->conditions),
            'condition_groups' => ConditionGroupResource::collection($this->conditionGroups),
            'effects' => RuleEffectResource::collection($this->effects),
            'triggers' => RuleTriggerResource::collection($this->triggers),
            'references' => RuleReferenceResource::collection($this->references),
            'victory_conditions' => VictoryConditionResource::collection($this->victoryConditions),
            'defeat_conditions' => DefeatConditionResource::collection($this->defeatConditions),
            'end_conditions' => GameEndConditionResource::collection($this->endConditions),
            'graph' => RuleGraphResource::make($this->graph),
            'errors' => ValidationErrorResource::collection($this->errors),
            'warnings' => ValidationErrorResource::collection($this->warnings),
        ];
    }
}
