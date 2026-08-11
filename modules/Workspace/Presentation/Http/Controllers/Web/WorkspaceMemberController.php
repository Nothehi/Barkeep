<?php

namespace Modules\Workspace\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Workspace\Application\Commands\ChangeWorkspaceMemberRole;
use Modules\Workspace\Application\Commands\InviteUserToWorkspace;
use Modules\Workspace\Application\Commands\RemoveWorkspaceMember;
use Modules\Workspace\Application\Commands\RevokeWorkspaceInvitation;
use Modules\Workspace\Application\Queries\GetPendingWorkspaceInvitations;
use Modules\Workspace\Application\Queries\GetWorkspaceMembers;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Domain\Models\WorkspaceInvitation;
use Modules\Workspace\Domain\Models\WorkspaceMember;
use Modules\Workspace\Presentation\Http\Requests\ChangeMemberRoleRequest;
use Modules\Workspace\Presentation\Http\Requests\InviteMemberRequest;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceInvitationResource;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceMemberResource;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * The workspace members screen and the changes it can make.
 *
 * Both `{member}` and `{invitation}` are resolved by scoped bindings, so an
 * id belonging to another workspace never reaches an action here.
 */
class WorkspaceMemberController extends Controller
{
    /**
     * Show the workspace's members and outstanding invitations.
     *
     * Any member may see who else is in the workspace; only administrators
     * are shown the pending invitations, since only they can act on them.
     */
    public function index(
        Workspace $workspace,
        GetWorkspaceMembers $getWorkspaceMembers,
        GetPendingWorkspaceInvitations $getPendingInvitations,
    ): Response {
        Gate::authorize('viewMembers', $workspace);

        $canManageInvitations = Gate::allows('manageInvitations', $workspace);

        return Inertia::render('workspaces/members', [
            'workspace' => WorkspaceResource::make($workspace->loadCount('members')),
            'members' => WorkspaceMemberResource::collection(
                $getWorkspaceMembers->handle($workspace),
            ),
            'invitations' => $canManageInvitations
                ? WorkspaceInvitationResource::collection($getPendingInvitations->handle($workspace))
                : WorkspaceInvitationResource::collection([]),
        ]);
    }

    /**
     * Invite an address to join the workspace.
     */
    public function invite(
        InviteMemberRequest $request,
        Workspace $workspace,
        InviteUserToWorkspace $inviteUser,
    ): RedirectResponse {
        $inviteUser->handle($request->user(), $workspace, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

        return to_route('workspaces.members.index', $workspace);
    }

    /**
     * Withdraw an invitation that has not been redeemed.
     */
    public function revokeInvitation(
        Request $request,
        Workspace $workspace,
        WorkspaceInvitation $invitation,
        RevokeWorkspaceInvitation $revokeInvitation,
    ): RedirectResponse {
        Gate::authorize('manageInvitations', $workspace);

        $revokeInvitation->handle($request->user(), $invitation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation revoked.')]);

        return to_route('workspaces.members.index', $workspace);
    }

    /**
     * Promote or demote a member.
     */
    public function update(
        ChangeMemberRoleRequest $request,
        Workspace $workspace,
        WorkspaceMember $member,
        ChangeWorkspaceMemberRole $changeRole,
    ): RedirectResponse {
        $changeRole->handle($request->user(), $workspace, $member, $request->role());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member role updated.')]);

        return to_route('workspaces.members.index', $workspace);
    }

    /**
     * Remove a member from the workspace.
     */
    public function destroy(
        Request $request,
        Workspace $workspace,
        WorkspaceMember $member,
        RemoveWorkspaceMember $removeMember,
    ): RedirectResponse {
        Gate::authorize('removeMembers', [$workspace, $member]);

        $removeMember->handle($request->user(), $workspace, $member);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed.')]);

        return to_route('workspaces.members.index', $workspace);
    }
}
