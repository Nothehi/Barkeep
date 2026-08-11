<?php

namespace Modules\Workspace\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Workspace\Application\Commands\ArchiveWorkspace;
use Modules\Workspace\Application\Commands\TransferWorkspaceOwnership;
use Modules\Workspace\Application\Commands\UpdateWorkspace;
use Modules\Workspace\Application\Queries\GetWorkspaceMembers;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Requests\TransferOwnershipRequest;
use Modules\Workspace\Presentation\Http\Requests\UpdateWorkspaceRequest;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceMemberResource;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * The workspace settings screen and the changes it can make.
 *
 * The danger zone — archival and ownership transfer — lives here rather than
 * with the member screens, because both end the current owner's relationship
 * with the workspace as they know it.
 */
class WorkspaceSettingsController extends Controller
{
    /**
     * Show the workspace settings screen.
     */
    public function edit(Workspace $workspace, GetWorkspaceMembers $getWorkspaceMembers): Response
    {
        Gate::authorize('view', $workspace);

        return Inertia::render('workspaces/settings', [
            'workspace' => WorkspaceResource::make($workspace->loadCount('members')),
            'members' => WorkspaceMemberResource::collection(
                $getWorkspaceMembers->handle($workspace),
            ),
        ]);
    }

    /**
     * Save the workspace's general settings.
     */
    public function update(
        UpdateWorkspaceRequest $request,
        Workspace $workspace,
        UpdateWorkspace $updateWorkspace,
    ): RedirectResponse {
        $updateWorkspace->handle($request->user(), $workspace, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace updated.')]);

        /** Renaming may have moved the address, so redirect to where it is now. */
        return to_route('workspaces.settings.edit', $workspace->fresh());
    }

    /**
     * Retire the workspace.
     */
    public function archive(
        Request $request,
        Workspace $workspace,
        ArchiveWorkspace $archiveWorkspace,
    ): RedirectResponse {
        Gate::authorize('archive', $workspace);

        $archiveWorkspace->handle($request->user(), $workspace);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace archived.')]);

        return to_route('workspaces.index');
    }

    /**
     * Hand the workspace to another member.
     */
    public function transferOwnership(
        TransferOwnershipRequest $request,
        Workspace $workspace,
        TransferWorkspaceOwnership $transferOwnership,
    ): RedirectResponse {
        $transferOwnership->handle(
            $request->user(),
            $workspace,
            $request->newOwner(),
            $request->outgoingOwnerRole(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ownership transferred.')]);

        return to_route('workspaces.settings.edit', $workspace);
    }
}
