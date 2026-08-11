<?php

namespace Modules\Workspace\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\Workspace\Application\Commands\InviteUserToWorkspace;
use Modules\Workspace\Application\Queries\GetPendingWorkspaceInvitations;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Requests\InviteMemberRequest;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceInvitationResource;

/**
 * The invitations a workspace has outstanding.
 *
 * Nested under the workspace because these are the administrators' view of
 * their own pending invites. The recipient's side of the same records lives
 * on {@see WorkspaceInvitationController}, which is addressed by token
 * instead.
 */
class WorkspaceMemberInvitationController extends Controller
{
    /**
     * List the workspace's outstanding invitations.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        GetPendingWorkspaceInvitations $getPendingInvitations,
    ): AnonymousResourceCollection {
        Gate::authorize('manageInvitations', $workspace);

        return WorkspaceInvitationResource::collection(
            $getPendingInvitations->handle($workspace),
        );
    }

    /**
     * Invite an address to join the workspace.
     */
    public function store(
        InviteMemberRequest $request,
        Workspace $workspace,
        InviteUserToWorkspace $inviteUser,
    ): JsonResponse {
        $invitation = $inviteUser->handle($request->user(), $workspace, $request->toData());

        return WorkspaceInvitationResource::make($invitation->load('creator'))
            ->response()
            ->setStatusCode(201);
    }
}
