<?php

namespace Modules\Workspace\Presentation\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Application\DTOs\CreateWorkspaceData;
use Modules\Workspace\Application\Validation\WorkspaceValidationRules;
use Modules\Workspace\Domain\Models\Workspace;

class CreateWorkspaceRequest extends FormRequest
{
    use WorkspaceValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->can('create', Workspace::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The address is optional here. Leaving it out asks the application layer
     * to derive one from the name; supplying it means that exact address, so
     * a collision is reported rather than worked around.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->workspaceRules(slugIsRequired: false);
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): CreateWorkspaceData
    {
        return CreateWorkspaceData::fromArray($this->validated());
    }
}
