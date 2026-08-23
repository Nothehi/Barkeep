<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\OutcomeData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Recording a victory, defeat or end condition.
 *
 * One request for the three, unlike the models. The three *records* stay separate
 * because winning, losing and stopping are three different questions a game
 * answers at once — but the fields a form collects for them are identical, and
 * three copies of this class would be three places for a length limit to drift.
 */
class CreateOutcomeRequest extends RuleSetRequest
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
            'name' => $this->statementNameRules(),
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
