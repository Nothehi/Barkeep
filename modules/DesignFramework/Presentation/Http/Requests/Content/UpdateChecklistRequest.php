<?php

namespace Modules\DesignFramework\Presentation\Http\Requests\Content;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\DesignFramework\Application\DTOs\ContentData;
use Modules\DesignFramework\Application\Validation\FrameworkValidationRules;
use Modules\DesignFramework\Presentation\Http\Requests\FrameworkRequest;

/**
 * Editing a readiness gate.
 */
class UpdateChecklistRequest extends FrameworkRequest
{
    use FrameworkValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * `updateVersion` answers both halves at once: may this account edit frameworks, and is this
     * edition still a draft. Content has no policy of its own, because everything inside a version is
     * governed by the version's own editability — which is exactly the rule section 47 asks for.
     */
    public function authorize(): Response
    {
        return $this->inspectVersion('updateVersion');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Everything is `sometimes`, which is what lets the DTO tell "left this field alone" from
     * "cleared this field" — so a form saving a description cannot blank the instructions beside it.
     *
     * Sending `phase_id` moves the content between phases; omitting it leaves it where it is. The
     * two are the same value and different intentions, which is why the distinction is kept.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => $this->contentTitleRules(required: false),
            'description' => $this->contentDescriptionRules(),
            'phase_id' => $this->phaseReferenceRules($this->version()),
            'status' => $this->contentStatusRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): ContentData
    {
        return ContentData::fromArray($this->validated());
    }
}
