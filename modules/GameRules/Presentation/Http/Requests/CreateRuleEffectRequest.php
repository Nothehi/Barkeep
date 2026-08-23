<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\EffectData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Recording what happens when a rule or an action resolves.
 *
 * Structured fields, never a script. "RESOURCE / Victory points / +3" is what the
 * rulebook says, not an instruction anything will carry out — see section 33 of
 * the brief.
 */
class CreateRuleEffectRequest extends RuleSetRequest
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
            'effect_type' => $this->effectTypeRules(),
            'target' => $this->effectTargetRules(),
            'value' => $this->valueRules(),
            'description' => $this->descriptionRules(2000),
            'economy_resource_slug' => $this->economyHandleRules(),
            'position' => $this->positionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): EffectData
    {
        return EffectData::fromArray($this->validated());
    }
}
