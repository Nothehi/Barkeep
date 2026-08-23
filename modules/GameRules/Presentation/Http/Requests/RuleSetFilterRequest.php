<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\GameRules\Application\DTOs\RuleSetFilters;
use Modules\GameRules\Application\Validation\RuleValidationRules;

/**
 * Reading the rule sets of a design state, optionally narrowed.
 */
class RuleSetFilterRequest extends RuleSetRequest
{
    use RuleValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectVersion('viewAny');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->ruleSetFilterRules();
    }

    /**
     * Get the validated query string as an application layer DTO.
     */
    public function toFilters(): RuleSetFilters
    {
        return RuleSetFilters::fromArray($this->validated());
    }
}
