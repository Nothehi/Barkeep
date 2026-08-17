<?php

namespace Modules\DesignFramework\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\DesignFramework\Application\DTOs\ChecklistItemData;
use Modules\DesignFramework\Application\Validation\FrameworkValidationRules;

class UpdateChecklistItemRequest extends FrameworkRequest
{
    use FrameworkValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectVersion('updateVersion');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * `required` is `sometimes`, so a partial request cannot silently promote an optional item into
     * a required one — which would move every following game's progress.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => $this->contentTitleRules(required: false),
            'description' => $this->contentDescriptionRules(),
            'required' => $this->requiredFlagRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): ChecklistItemData
    {
        return ChecklistItemData::fromArray($this->validated());
    }
}
