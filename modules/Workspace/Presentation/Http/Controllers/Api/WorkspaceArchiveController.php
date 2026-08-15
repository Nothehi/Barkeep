<?php

namespace Modules\Workspace\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Workspace\Application\Commands\ArchiveWorkspace;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * Retiring a workspace.
 *
 * Deliberately not `DELETE /workspaces/{workspace}`: nothing is destroyed,
 * and a route that looked like a delete would invite somebody to implement
 * one later.
 */
class WorkspaceArchiveController extends Controller
{
    /**
     * Archive the workspace.
     */
    public function store(Request $request, Workspace $workspace, ArchiveWorkspace $archiveWorkspace): WorkspaceResource
    {
        Gate::authorize('archive', $workspace);

        $archiveWorkspace->handle($request->user(), $workspace);

        return WorkspaceResource::make($workspace->loadCount('members'));
    }
}
