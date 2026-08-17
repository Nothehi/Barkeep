<?php

namespace Modules\DesignFramework\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\DesignFramework\Application\DTOs\FrameworkVersionData;
use Modules\DesignFramework\Application\Validation\FrameworkValidationRules;

class UpdateFrameworkVersionRequest extends FrameworkRequest
{
    use FrameworkValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * `updateVersion` answers both halves at once: may this account edit frameworks, and is this
     * version still a draft. A published version refuses for everybody, however privileged.
     */
    public function authorize(): Response
    {
        return $this->inspectVersion('updateVersion');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => $this->versionNameRules(),
            'description' => $this->frameworkDescriptionRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): FrameworkVersionData
    {
        return FrameworkVersionData::fromArray($this->validated());
    }
}
