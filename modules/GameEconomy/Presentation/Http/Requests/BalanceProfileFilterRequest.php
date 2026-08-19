<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\BalanceProfileFilters;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class BalanceProfileFilterRequest extends BalanceRequest
{
    use EconomyValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectVersion('viewAny');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileFilterRules();
    }

    /**
     * Get the validated query string as an application layer DTO.
     */
    public function toFilters(): BalanceProfileFilters
    {
        return BalanceProfileFilters::fromArray($this->validated());
    }
}
