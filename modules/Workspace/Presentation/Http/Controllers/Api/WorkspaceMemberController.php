<?php

namespace Modules\Workspace\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Modules\Workspace\Application\Commands\ChangeWorkspaceMemberRole;
use Modules\Workspace\Application\Commands\RemoveWorkspaceMember;
use Modules\Workspace\Application\Queries\GetWorkspaceMembers;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceMember;
use Modules\Workspace\Presentation\Http\Requests\ChangeMemberRoleRequest;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceMemberResource;

/**
 * A workspace's membership.
 *
 * The `{member}` segment uses a scoped binding, so a membership id from
 * another workspace does not resolve at all — the request 404s before any
 * policy runs, and cannot be used to read or change somebody else's team.
 *
 * @see ChangeMemberRoleRequest
 */
class WorkspaceMemberController extends Controller
{
    /**
     * List the workspace's members.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        GetWorkspaceMembers $getWorkspaceMembers,
    ): AnonymousResourceCollection {
        Gate::authorize('viewMembers', $workspace);

        return WorkspaceMemberResource::collection(
            $getWorkspaceMembers->handle($workspace),
        );
    }

    /**
     * Promote or demote a member.
     */
    public function update(
        ChangeMemberRoleRequest $request,
        Workspace $workspace,
        WorkspaceMember $member,
        ChangeWorkspaceMemberRole $changeRole,
    ): WorkspaceMemberResource {
        $changeRole->handle($request->user(), $workspace, $member, $request->role());

        return WorkspaceMemberResource::make($member->load('user'));
    }

    /**
     * Remove a member from the workspace.
     */
    public function destroy(
        Request $request,
        Workspace $workspace,
        WorkspaceMember $member,
        RemoveWorkspaceMember $removeMember,
    ): Response {
        Gate::authorize('removeMembers', [$workspace, $member]);

        $removeMember->handle($request->user(), $workspace, $member);

        return response()->noContent();
    }
}
