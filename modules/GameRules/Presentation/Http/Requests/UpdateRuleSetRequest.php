<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\RuleSetData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Correcting a rule set's own title or summary.
 *
 * Authorized against `rename` rather than `edit`, and it is the only request in
 * the module that is. An active rule set refuses every change to its rules and
 * still accepts this one, because a title is a label on the document rather than
 * part of what a session was played under.
 */
class UpdateRuleSetRequest extends RuleSetRequest
{
    use RuleValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectRename();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * No status. Activating and archiving are actions with their own endpoints,
     * which keeps an irreversible move from being one field value away from a
     * reversible one.
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
    public function toData(): RuleSetData
    {
        return RuleSetData::fromArray($this->validated());
    }
}
