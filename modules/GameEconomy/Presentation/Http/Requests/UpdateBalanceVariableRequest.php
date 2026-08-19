<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\BalanceVariableData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class UpdateBalanceVariableRequest extends BalanceRequest
{
    use EconomyValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectConfiguration();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Every field is optional, because this is the endpoint the variable table's
     * inline editing uses. A cell that sends only `value` must not be refused
     * for omitting the name, and must not clear the unit or the range around it.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $profile = $this->profile();

        return [
            'name' => $this->variableNameRules(required: false),
            'description' => $this->descriptionRules(),
            'value' => $this->optionalAmountRules(),
            'unit' => $this->unitRules(),
            'min_value' => $this->optionalAmountRules(),
            'max_value' => $this->optionalAmountRules(),
            'step' => $this->stepRules(),
            'category' => $this->variableCategoryRules(),
            'resource_type_id' => $this->optionalResourceReferenceRules($profile),
            'action_id' => $this->optionalActionReferenceRules($profile),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): BalanceVariableData
    {
        return BalanceVariableData::fromArray($this->validated());
    }
}
