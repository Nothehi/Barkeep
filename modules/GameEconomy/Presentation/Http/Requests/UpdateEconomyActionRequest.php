<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\EconomyActionData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class UpdateEconomyActionRequest extends BalanceRequest
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
            'name' => $this->actionNameRules(required: false),
            'description' => $this->descriptionRules(),
            'position' => $this->positionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): EconomyActionData
    {
        return EconomyActionData::fromArray($this->validated());
    }
}
