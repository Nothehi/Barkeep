<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\ActionLineData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

/**
 * Retuning what an action costs or pays out.
 *
 * The resource is not accepted, which is the deliberate difference from
 * {@see AddActionLineRequest}. Changing which resource a line is about is not an
 * edit to the price — it is removing one and adding another — and letting a
 * PATCH do it would make the unique constraint on (action, resource) reachable
 * through a route that never mentions it.
 */
class UpdateActionLineRequest extends BalanceRequest
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
            'amount' => $this->magnitudeRules(required: false),
            'is_variable' => $this->flagRules(),
            'min_amount' => $this->optionalAmountRules(),
            'max_amount' => $this->optionalAmountRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): ActionLineData
    {
        return ActionLineData::fromArray($this->validated());
    }
}
