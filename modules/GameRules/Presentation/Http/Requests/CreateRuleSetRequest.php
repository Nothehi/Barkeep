<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\RuleSetData;
use Modules\GameRules\Application\Validation\RuleValidationRules;

class CreateRuleSetRequest extends RuleSetRequest
{
    use RuleValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectVersion('create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Nothing about the rules themselves, deliberately: a rule set is created
     * empty and then written. A create form asking for a first phase would be
     * asking a designer to have finished before they had started.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->ruleSetNameRules(),
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
