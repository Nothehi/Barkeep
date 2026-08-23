<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\RequirementData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Saying what has to be true before a rule or an action applies.
 *
 * Both owner fields are optional here and exactly one is required by the command.
 * The validator cannot express "one of these two" without duplicating the rule,
 * and the command has to check it anyway for callers that never touch a form.
 */
class CreateRuleRequirementRequest extends RuleSetRequest
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
            'rule_id' => $this->ruleReferenceRules($ruleSet),
            'action_id' => $this->actionReferenceRules($ruleSet),
            'requirement_type' => $this->requirementTypeRules(),
            'description' => $this->requirementDescriptionRules(),
            'value' => $this->valueRules(),
            'economy_resource_slug' => $this->economyHandleRules(),
            'position' => $this->positionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): RequirementData
    {
        return RequirementData::fromArray($this->validated());
    }
}
