<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\BalanceScenarioData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class CreateBalanceScenarioRequest extends BalanceRequest
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
     * The overrides are absent: setting a value in a scenario is its own
     * endpoint, because that is how a scenario is built — somebody names "Rich
     * economy" and then works through the numbers one at a time.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->scenarioNameRules(),
            'description' => $this->descriptionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): BalanceScenarioData
    {
        return BalanceScenarioData::fromArray($this->validated());
    }
}
