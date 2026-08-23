<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\ReferenceData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Recording how one rule relates to another.
 *
 * The referenced rule is checked here against the referring rule's own set, which
 * is what keeps an edge from spanning two rule systems — `rule_references` has no
 * `rule_set_id` of its own, so this lookup is the only thing enforcing it.
 */
class CreateRuleReferenceRequest extends RuleSetRequest
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
            'referenced_rule_id' => $this->ruleReferenceRules($ruleSet, required: true),
            'reference_type' => $this->referenceTypeRules(),
            'description' => $this->descriptionRules(2000),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): ReferenceData
    {
        return ReferenceData::fromArray($this->validated());
    }
}
