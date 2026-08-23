<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\GameRuleData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Rewording a rule, retyping it, moving it in the tree, or retiring it.
 *
 * `parent_rule_id` is checked here for belonging to the rule set and again by the
 * command for whether the move would make the rule its own ancestor. The second
 * check cannot live in a validator: it needs the whole hierarchy, and it is a
 * refusal about the *move* rather than about the value.
 */
class UpdateGameRuleRequest extends RuleSetRequest
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
            'parent_rule_id' => $this->ruleReferenceRules($ruleSet),
            'phase_id' => $this->phaseReferenceRules($ruleSet),
            'rule_type' => $this->ruleTypeRules(),
            'status' => $this->ruleStatusRules(),
            'position' => $this->positionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): GameRuleData
    {
        return GameRuleData::fromArray($this->validated());
    }
}
