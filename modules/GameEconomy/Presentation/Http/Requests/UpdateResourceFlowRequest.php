<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\ResourceFlowData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class UpdateResourceFlowRequest extends BalanceRequest
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
     * The resource may be changed — "actually that harvest is clay, not wood" is
     * an ordinary correction — and the replacement is checked against this
     * profile exactly as the original was.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resource_type_id' => $this->resourceReferenceRules($this->profile(), required: false),
            'name' => $this->flowNameRules(required: false),
            'description' => $this->descriptionRules(),
            'flow_type' => $this->flowTypeRules(),
            'amount' => $this->magnitudeRules(required: false),
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
