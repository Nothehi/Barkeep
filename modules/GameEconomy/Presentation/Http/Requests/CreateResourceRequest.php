<?php

namespace Modules\GameEconomy\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameEconomy\Application\DTOs\ResourceTypeData;
use Modules\GameEconomy\Application\Validation\EconomyValidationRules;

class CreateResourceRequest extends BalanceRequest
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
     * There is no rule for the slug: it is derived from the name, so a caller
     * cannot set one — which is what keeps a resource's handle and its name from
     * disagreeing.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->resourceNameRules(),
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
