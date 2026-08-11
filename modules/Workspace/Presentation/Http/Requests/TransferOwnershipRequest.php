<?php

namespace Modules\Workspace\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Modules\Workspace\Application\Validation\WorkspaceValidationRules;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Models\WorkspaceMember;

class TransferOwnershipRequest extends WorkspaceRequest
{
    use WorkspaceValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspect('transferOwnership');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The incoming owner is identified by membership id and the existence
     * rule is scoped to this workspace, so a membership from somewhere else
     * fails validation rather than reaching the application layer.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'member_id' => [
                'required',
                'string',
                Rule::exists(WorkspaceMember::class, 'id')
                    ->where('workspace_id', $this->workspace()->id),
            ],
            'role' => $this->assignableRoleRules(required: false),
        ];
    }

    /**
     * The member who should become the owner.
     */
    public function newOwner(): WorkspaceMember
    {
        return $this->workspace()->members()
            ->whereKey((string) $this->validated('member_id'))
            ->firstOrFail();
    }

    /**
     * The role the outgoing owner should be left with.
     *
     * Defaults to administrator: somebody who has just handed over their
     * workspace almost always still needs to work in it.
     */
    public function outgoingOwnerRole(): WorkspaceRole
    {
        $role = $this->validated('role');

        return is_string($role) && $role !== ''
            ? WorkspaceRole::from($role)
            : WorkspaceRole::Admin;
    }
}
