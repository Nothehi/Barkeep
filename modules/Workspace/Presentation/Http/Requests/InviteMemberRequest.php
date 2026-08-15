<?php

namespace Modules\Workspace\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Workspace\Application\DTOs\InviteMemberData;
use Modules\Workspace\Application\Validation\WorkspaceValidationRules;

class InviteMemberRequest extends WorkspaceRequest
{
    use WorkspaceValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspect('inviteMembers');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => $this->invitationEmailRules(),
            'role' => $this->assignableRoleRules(required: false),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): InviteMemberData
    {
        return InviteMemberData::fromArray($this->validated());
    }
}
