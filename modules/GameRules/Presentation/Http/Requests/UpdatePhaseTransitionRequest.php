<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\PhaseTransitionData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Changing where a transition leads, what guards it, or when it is considered.
 */
class UpdatePhaseTransitionRequest extends RuleSetRequest
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
            'from_phase_id' => $this->phaseReferenceRules($ruleSet),
            'to_phase_id' => $this->phaseReferenceRules($ruleSet),
            'condition_id' => $this->conditionReferenceRules($ruleSet),
            'trigger_id' => $this->triggerReferenceRules($ruleSet),
            'position' => $this->positionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): PhaseTransitionData
    {
        return PhaseTransitionData::fromArray($this->validated());
    }
}
