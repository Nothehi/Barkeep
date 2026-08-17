<?php

namespace Modules\DesignFramework\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\DesignFramework\Application\DTOs\FrameworkVersionData;
use Modules\DesignFramework\Application\Validation\FrameworkValidationRules;

class CreateFrameworkVersionRequest extends FrameworkRequest
{
    use FrameworkValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * `createVersion` rather than `update`, and the difference is load-bearing: a *published*
     * framework refuses edits to its own record and accepts new draft versions, which is the
     * mechanism by which a methodology evolves at all.
     */
    public function authorize(): Response
    {
        return $this->inspectFramework('createVersion');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The version number is absent because it is not an input: it is allocated by the command.
     * Version numbers are cited by the games that adopt them, so a caller allowed to name its own
     * could overwrite the meaning of an edition somebody is following.
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
