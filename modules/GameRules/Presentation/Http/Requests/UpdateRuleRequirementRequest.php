<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\RequirementData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Rewording a requirement, changing its threshold, or re-pricing it.
 *
 * The owner is not editable. Moving a requirement between actions is two
 * operations, and offering it as one would let a `rule_id` in a PATCH body
 * silently relocate a gate.
 */
class UpdateRuleRequirementRequest extends RuleSetRequest
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
            'requirement_type' => $this->requirementTypeRules(),
            'description' => $this->requirementDescriptionRules(required: false),
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
