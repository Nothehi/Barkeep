<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\GamePhaseData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Adding a stage of play.
 *
 * A phase of the *game* — setup, the action phase, cleanup — and not a phase of
 * the designer's work, which is DesignFramework's and unrelated.
 */
class CreateGamePhaseRequest extends RuleSetRequest
{
    use RuleValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectEdit();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $ruleSet = $this->ruleSet();

        return [
            'name' => $this->nameRules(),
            'description' => $this->descriptionRules(),
            'parent_phase_id' => $this->phaseReferenceRules($ruleSet),
            'phase_type' => $this->phaseTypeRules(),
            'status' => $this->ruleStatusRules(),
            'position' => $this->positionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): GamePhaseData
    {
        return GamePhaseData::fromArray($this->validated());
    }
}
