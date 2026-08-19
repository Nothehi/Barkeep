<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\EconomyActionData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class CreateEconomyActionRequest extends BalanceRequest
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
     * Costs, rewards and effects are absent, and that is deliberate: an action
     * is created empty and then priced. A form that demanded a resource list
     * would also demand that the resources already existed, which is the wrong
     * way round for a new configuration.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->actionNameRules(),
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
