<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\CloneRuleSetData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Copying a rule set into a fresh draft.
 *
 * Both fields are optional, and that is the point. Cloning is what an active rule
 * set offers instead of editing, so it has to work with one press — a required
 * name would be a small tax on the operation the module most wants people to
 * reach for.
 */
class CloneRuleSetRequest extends RuleSetRequest
{
    use RuleValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectRuleSet('clone');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->ruleSetNameRules(required: false),
            'description' => $this->descriptionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): CloneRuleSetData
    {
        return CloneRuleSetData::fromArray($this->validated());
    }
}
