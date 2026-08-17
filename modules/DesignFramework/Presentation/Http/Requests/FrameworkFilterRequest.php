<?php

namespace Modules\DesignFramework\Presentation\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Modules\DesignFramework\Application\DTOs\FrameworkFilters;
use Modules\DesignFramework\Application\Validation\FrameworkValidationRules;

class FrameworkFilterRequest extends FrameworkRequest
{
    use FrameworkValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Every signed in account may see the frameworks list. Which frameworks appear in it is the
     * query's business, and drafts are excluded unless the controller asks for them after checking
     * the policy — a distinction the filters deliberately cannot influence.
     */
    public function authorize(): bool
    {
        return $this->actor() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->frameworkFilterRules();
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toFilters(): FrameworkFilters
    {
        return FrameworkFilters::fromArray($this->validated());
    }
}
