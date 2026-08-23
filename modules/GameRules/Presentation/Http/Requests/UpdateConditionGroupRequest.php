<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\ConditionGroupData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Renaming a group, or switching it between "all of these" and "any of these".
 */
class UpdateConditionGroupRequest extends RuleSetRequest
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
            'logic_operator' => $this->logicOperatorRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): ConditionGroupData
    {
        return ConditionGroupData::fromArray($this->validated());
    }
}
