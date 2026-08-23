<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\TriggerData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Naming something that happens automatically.
 *
 * Nothing here says what the trigger *does*. A trigger records when; what points
 * at it says what — see section 23 of the brief on why this module has no field
 * an execution loop could read.
 */
class CreateRuleTriggerRequest extends RuleSetRequest
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
            'name' => $this->statementNameRules(),
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
