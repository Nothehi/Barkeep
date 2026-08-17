<?php

namespace Modules\DesignFramework\Presentation\Http\Requests\Content;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\DesignFramework\Application\DTOs\ContentData;
use Modules\DesignFramework\Application\Validation\FrameworkValidationRules;
use Modules\DesignFramework\Presentation\Http\Requests\FrameworkRequest;

/**
 * Writing a design rule the methodology asserts.
 */
class CreatePrincipleRequest extends FrameworkRequest
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
     * The phase is the one identifier here that does not come from the URL, so it is checked
     * explicitly — through the version that owns it, so a phase from another edition is not found
     * rather than being found and rejected. A null phase is a real choice: it files the content
     * across the whole methodology.
     *
     * No position and no address. The position is allocated by `ContentSequencer` and changed only
     * by an explicit reorder; the address is derived from the title on creation and then left alone
     * so it stays a stable handle.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => $this->contentTitleRules(),
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
