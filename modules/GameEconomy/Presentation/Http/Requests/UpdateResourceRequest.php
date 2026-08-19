<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\ResourceTypeData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class UpdateResourceRequest extends BalanceRequest
{
    use EconomyValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspect('configure', [$this->resourceType()->profile]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Every field is optional, because this is the endpoint the resource list's
     * inline editors use as well as the full form. A rule set that required the
     * name would make "set the cap" impossible to send on its own.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->resourceNameRules(required: false),
            'description' => $this->descriptionRules(),
            'unit' => $this->unitRules(),
            'category' => $this->resourceCategoryRules(),
            'is_tradeable' => $this->flagRules(),
            'is_accumulative' => $this->flagRules(),
            'is_spendable' => $this->flagRules(),
            'is_convertible' => $this->flagRules(),
            'min_value' => $this->optionalAmountRules(),
            'max_value' => $this->optionalAmountRules(),
            'starting_value' => $this->optionalAmountRules(),
            'position' => $this->positionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): ResourceTypeData
    {
        return ResourceTypeData::fromArray($this->validated());
    }
}
