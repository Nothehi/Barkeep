<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\OutcomeData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Rewording an outcome, attaching a condition to it, or reordering it.
 */
class UpdateOutcomeRequest extends RuleSetRequest
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
            'name' => $this->statementNameRules(required: false),
            'description' => $this->descriptionRules(2000),
            'condition_id' => $this->conditionReferenceRules($ruleSet),
            'priority' => $this->priorityRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): OutcomeData
    {
        return OutcomeData::fromArray($this->validated());
    }
}
