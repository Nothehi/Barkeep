<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\BalanceVariableData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class CreateBalanceVariableRequest extends BalanceRequest
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
     * The value is not checked against the range beside it. A designer setting a
     * range around a number they are about to change would be blocked by a form
     * that refused, so the analysis reports it instead — section 31.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $profile = $this->profile();

        return [
            'name' => $this->variableNameRules(),
            'description' => $this->descriptionRules(),
            'value' => $this->requiredAmountRules(),
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
