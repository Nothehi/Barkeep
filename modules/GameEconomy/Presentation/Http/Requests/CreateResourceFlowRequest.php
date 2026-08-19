<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\ResourceFlowData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class CreateResourceFlowRequest extends BalanceRequest
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
     * The resource is the only identifier a caller supplies here, and it is
     * checked against this profile — so a flow cannot claim to move somebody
     * else's wood.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resource_type_id' => $this->resourceReferenceRules($this->profile()),
            'name' => $this->flowNameRules(),
            'description' => $this->descriptionRules(),
            'flow_type' => $this->flowTypeRules(),
            'amount' => $this->magnitudeRules(),
            'condition' => $this->conditionRules(),
            'position' => $this->positionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): ResourceFlowData
    {
        return ResourceFlowData::fromArray($this->validated());
    }
}
