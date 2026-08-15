<?php

namespace Modules\Workspace\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Workspace\Application\DTOs\UpdateWorkspaceData;
use Modules\Workspace\Application\Validation\WorkspaceValidationRules;

class UpdateWorkspaceRequest extends WorkspaceRequest
{
    use WorkspaceValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspect('update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->workspaceRules($this->workspace()->id);
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): UpdateWorkspaceData
    {
        return UpdateWorkspaceData::fromArray($this->validated());
    }
}
