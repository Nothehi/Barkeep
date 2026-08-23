<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\TriggerData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Renaming a trigger or changing when it fires.
 */
class UpdateRuleTriggerRequest extends RuleSetRequest
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
            'name' => $this->statementNameRules(required: false),
            'description' => $this->descriptionRules(2000),
            'trigger_type' => $this->triggerTypeRules(),
            'position' => $this->positionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): TriggerData
    {
        return TriggerData::fromArray($this->validated());
    }
}
