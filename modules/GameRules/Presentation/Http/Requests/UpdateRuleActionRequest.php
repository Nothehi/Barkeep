<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\RuleActionData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Renaming an action, moving it to another phase, or wiring it to the economy.
 */
class UpdateRuleActionRequest extends RuleSetRequest
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
            'name' => $this->nameRules(required: false),
            'description' => $this->descriptionRules(),
            'phase_id' => $this->phaseReferenceRules($ruleSet),
            'action_type' => $this->actionTypeRules(),
            'status' => $this->ruleStatusRules(),
            'economy_action_slug' => $this->economyHandleRules(),
            'position' => $this->positionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): RuleActionData
    {
        return RuleActionData::fromArray($this->validated());
    }
}
