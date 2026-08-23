<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\RuleActionData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Declaring something a player may do.
 *
 * The phase is optional here and an error in the validator, which is the right
 * order: an action is created before the turn structure is settled, and the
 * finding is what reminds somebody to come back to it.
 *
 * `economy_action_slug` is a handle and nothing more. What the action costs
 * belongs to GameEconomy — see section 16 of the module brief.
 */
class CreateRuleActionRequest extends RuleSetRequest
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
