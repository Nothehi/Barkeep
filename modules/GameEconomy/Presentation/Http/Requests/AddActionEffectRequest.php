<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\ActionEffectData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class AddActionEffectRequest extends BalanceRequest
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
     * The value is optional even for effect types that expect one. An unlock has
     * no magnitude, and a capacity modifier somebody has not yet decided the size
     * of is a real, half-finished state — the analysis says so rather than the
     * form refusing it.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'effect_type' => $this->effectTypeRules(),
            'target' => $this->effectTargetRules(),
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
