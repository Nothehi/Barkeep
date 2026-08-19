<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\ActionLineData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

/**
 * Pricing an action in a resource, or having it pay one out.
 *
 * One request for both, which is the one place in the module costs and rewards
 * are treated alike — and they are alike here because the *input* is identical:
 * a resource, an amount, and optionally a range. They diverge in what they mean,
 * which is why they remain separate tables, separate commands and separate
 * panels.
 *
 * The resource is resolved against the action's own profile, so an action cannot
 * be priced in another configuration's wood.
 */
class AddActionLineRequest extends BalanceRequest
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
     * The bounds are not checked against each other. A designer typing a range
     * from the wrong end is told by the analysis rather than by the form, for
     * the same reason nothing else in this module refuses a save: half-built
     * configurations are full of inconsistencies, and the tool has to let people
     * work through them.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resource_type_id' => $this->resourceReferenceRules($this->owningProfile()),
            'amount' => $this->magnitudeRules(),
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
