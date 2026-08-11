<?php

namespace Modules\Workspace\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Application\Commands\AcceptWorkspaceInvitation;
use Modules\Workspace\Application\Queries\GetWorkspaceInvitation;
use Modules\Workspace\Domain\Exceptions\InvitationIsNotAcceptable;
use Modules\Workspace\Domain\ValueObjects\InvitationToken;
use Modules\Workspace\Presentation\Http\Resources\PublicWorkspaceInvitationResource;

/**
 * The screen somebody lands on after clicking an invitation link.
 *
 * Reachable without a session, because the recipient may not have an account
 * yet — in which case the page sends them through Identity's registration and
 * they come back to the same link. Workspace never creates accounts itself.
 */
class WorkspaceInvitationController extends Controller
{
    /**
     * Show what the invitation link leads to.
     */
    public function show(Request $request, string $token, GetWorkspaceInvitation $getInvitation): Response
    {
        $invitation = $getInvitation->handle(InvitationToken::fromString($token));

        if ($invitation === null) {
            throw InvitationIsNotAcceptable::notFound();
        }

        $user = $request->user();

        return Inertia::render('workspaces/invitation', [
            'invitation' => PublicWorkspaceInvitationResource::make($invitation),
            'token' => $token,

            /**
             * Whether the signed in account is the one that was invited. The
             * server checks this again when the invitation is accepted; here
             * it only decides which message the page shows.
             */
            'matchesAccount' => $user instanceof User && $invitation->wasSentTo($user),
        ]);
    }

    /**
     * Redeem the invitation and go into the workspace.
     */
    public function accept(
        Request $request,
        string $token,
        AcceptWorkspaceInvitation $acceptInvitation,
    ): RedirectResponse {
        $member = $acceptInvitation->handle($request->user(), InvitationToken::fromString($token));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Welcome to the workspace.')]);

        return to_route('workspaces.show', $member->workspace);
    }
}
