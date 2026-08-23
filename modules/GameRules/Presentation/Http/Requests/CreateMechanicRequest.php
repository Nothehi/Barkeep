<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\MechanicData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Naming a mechanism this rule system uses.
 */
class CreateMechanicRequest extends RuleSetRequest
{
    use RuleValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectEdit();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $ruleSet = $this->ruleSet();

        return [
            'name' => $this->nameRules(),
            'description' => $this->descriptionRules(),
            'category' => $this->mechanicCategoryRules(),
            'position' => $this->positionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): MechanicData
    {
        return MechanicData::fromArray($this->validated());
    }
}
