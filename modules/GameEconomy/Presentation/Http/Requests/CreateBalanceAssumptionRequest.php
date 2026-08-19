<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\BalanceAssumptionData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class CreateBalanceAssumptionRequest extends BalanceRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => $this->assumptionTitleRules(),
            'description' => $this->descriptionRules(),
            'category' => $this->assumptionCategoryRules(),
            'confidence' => $this->confidenceRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): BalanceAssumptionData
    {
        return BalanceAssumptionData::fromArray($this->validated());
    }
}
