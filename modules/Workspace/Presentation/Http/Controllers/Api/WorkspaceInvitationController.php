<?php

namespace Modules\Workspace\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Workspace\Application\Commands\AcceptWorkspaceInvitation;
use Modules\Workspace\Application\Commands\RevokeWorkspaceInvitation;
use Modules\Workspace\Application\Queries\GetWorkspaceInvitation;
use Modules\Workspace\Domain\Exceptions\InvitationIsNotAcceptable;
use Modules\Workspace\Domain\Models\WorkspaceInvitation;
use Modules\Workspace\Domain\ValueObjects\InvitationToken;
use Modules\Workspace\Presentation\Http\Resources\PublicWorkspaceInvitationResource;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceInvitationResource;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceMemberResource;

/**
 * The recipient's side of an invitation.
 *
 * Addressed by token rather than by workspace, because the person holding the
 * link is not a member yet and has nothing else to identify. Everything about
 * where they are joining and as what is read out of the invitation the token
 * resolves to — the caller supplies the token and nothing else.
 */
class WorkspaceInvitationController extends Controller
{
    /**
     * Show what an invitation link leads to.
     *
     * Readable without signing in, so the landing page can say which
     * workspace the link is for before asking anyone to register. The
     * response is trimmed to just that.
     *
     * @see PublicWorkspaceInvitationResource
     */
    public function show(string $token, GetWorkspaceInvitation $getInvitation): PublicWorkspaceInvitationResource
    {
        $invitation = $getInvitation->handle(InvitationToken::fromString($token));

        if ($invitation === null) {
            throw InvitationIsNotAcceptable::notFound();
        }

        return PublicWorkspaceInvitationResource::make($invitation);
    }

    /**
     * Redeem an invitation and join the workspace.
     */
    public function accept(
        Request $request,
        string $token,
        AcceptWorkspaceInvitation $acceptInvitation,
    ): JsonResponse {
        $member = $acceptInvitation->handle(
            $request->user(),
            InvitationToken::fromString($token),
        );

        return WorkspaceMemberResource::make($member->load('user'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Withdraw an invitation that has not been redeemed.
     *
     * Authorized against the workspace the invitation belongs to, not against
     * the invitation id the caller supplied, so a guessed id from another
     * workspace is refused.
     */
    public function revoke(
        Request $request,
        WorkspaceInvitation $invitation,
        RevokeWorkspaceInvitation $revokeInvitation,
    ): WorkspaceInvitationResource {
        $workspace = $invitation->workspace;

        if ($workspace === null) {
            throw InvitationIsNotAcceptable::notFound();
        }

        Gate::authorize('manageInvitations', $workspace);

        $revokeInvitation->handle($request->user(), $invitation);

        return WorkspaceInvitationResource::make($invitation->load('creator'));
    }
}
