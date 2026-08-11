<?php

namespace Modules\Workspace\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Workspace\Application\Validation\WorkspaceValidationRules;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Models\WorkspaceMember;
use RuntimeException;

class ChangeMemberRoleRequest extends WorkspaceRequest
{
    use WorkspaceValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * The member is passed to the policy as well as the workspace, because
     * who may be demoted depends on who they are — the owner may not be
     * touched this way at all.
     */
    public function authorize(): Response
    {
        return $this->inspect('changeMemberRole', [$this->member()]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => $this->assignableRoleRules(),
        ];
    }

    /**
     * The member whose role is changing.
     *
     * Resolved from the route's scoped binding, so a member id belonging to
     * another workspace never resolves in the first place.
     */
    public function member(): WorkspaceMember
    {
        $member = $this->route('member');

        if (! $member instanceof WorkspaceMember) {
            throw new RuntimeException(static::class.' was used on a route without a bound member.');
        }

        return $member;
    }

    /**
     * The role the member should end up with.
     */
    public function role(): WorkspaceRole
    {
        return WorkspaceRole::from((string) $this->validated('role'));
    }
}
