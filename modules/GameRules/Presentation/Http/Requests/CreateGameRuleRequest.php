<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\GameRuleData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Writing a rule down.
 *
 * Requirements and effects are absent: each has its own endpoint, because each is
 * a separate row and editing one must not be able to disturb another. A rule is
 * written first and gated afterwards, which is how designers work.
 */
class CreateGameRuleRequest extends RuleSetRequest
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
