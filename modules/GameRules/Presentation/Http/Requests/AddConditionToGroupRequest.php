<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Putting a condition into a group.
 *
 * The condition arrives in the body rather than as a route segment, because it is
 * not owned by the group — the same condition may be in several — so it is checked
 * against the group's own rule set here and resolved through it again by the
 * command.
 */
class AddConditionToGroupRequest extends RuleSetRequest
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
        return [
            'condition_id' => $this->conditionReferenceRules($this->ruleSet(), required: true),
        ];
    }

    /**
     * The condition being added.
     */
    public function conditionId(): string
    {
        return (string) $this->validated('condition_id');
    }
}
