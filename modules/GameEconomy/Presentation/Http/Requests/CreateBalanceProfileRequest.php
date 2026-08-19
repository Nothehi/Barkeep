<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\BalanceProfileData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class CreateBalanceProfileRequest extends BalanceRequest
{
    use EconomyValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectVersion('create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The design version is absent because it is not an input: it comes from the
     * resolved route binding. There is no rule for the status either — every
     * profile starts as a draft, and anything sent would be ignored, so it is
     * not accepted.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->profileNameRules(),
            'description' => $this->profileDescriptionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): BalanceProfileData
    {
        return BalanceProfileData::fromArray($this->validated());
    }
}
