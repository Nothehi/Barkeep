<?php

namespace Modules\DesignFramework\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\DesignFramework\Application\DTOs\UpdateFrameworkData;
use Modules\DesignFramework\Application\Validation\FrameworkValidationRules;

class UpdateFrameworkRequest extends FrameworkRequest
{
    use FrameworkValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * The ability folds in "the framework is still a draft", so a published framework refuses this
     * without the controller having to ask separately.
     */
    public function authorize(): Response
    {
        return $this->inspectFramework('update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Everything is `sometimes`, which is what lets the DTO tell "left this field alone" from
     * "cleared this field".
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->frameworkNameRules(required: false),
            'slug' => $this->frameworkSlugRules(),
            'description' => $this->frameworkDescriptionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): UpdateFrameworkData
    {
        return UpdateFrameworkData::fromArray($this->validated());
    }
}
