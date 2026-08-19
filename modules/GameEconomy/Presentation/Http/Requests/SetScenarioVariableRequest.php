<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\ScenarioVariableData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class SetScenarioVariableRequest extends BalanceRequest
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
     * The variable is checked against the scenario's own profile. An override
     * naming a variable from elsewhere would be stored and then change nothing
     * anybody could see, which is a worse failure than being refused.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'balance_variable_id' => $this->scenarioVariableRules($this->scenario()),
            'value' => $this->requiredAmountRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): ScenarioVariableData
    {
        return ScenarioVariableData::fromArray($this->validated());
    }
}
