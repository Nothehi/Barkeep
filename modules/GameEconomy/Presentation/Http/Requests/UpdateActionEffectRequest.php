<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\ActionEffectData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class UpdateActionEffectRequest extends BalanceRequest
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
            'effect_type' => $this->effectTypeRules(),
            'target' => $this->effectTargetRules(required: false),
            'value' => $this->optionalAmountRules(),
            'description' => $this->descriptionRules(2000),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): ActionEffectData
    {
        return ActionEffectData::fromArray($this->validated());
    }
}
