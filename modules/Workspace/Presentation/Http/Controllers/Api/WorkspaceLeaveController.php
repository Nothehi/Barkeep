<?php

namespace Modules\Workspace\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Modules\Workspace\Application\Commands\LeaveWorkspace;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Leaving a workspace of one's own accord.
 */
class WorkspaceLeaveController extends Controller
{
    /**
     * Give up the caller's own membership.
     */
    public function store(Request $request, Workspace $workspace, LeaveWorkspace $leaveWorkspace): Response
    {
        Gate::authorize('leave', $workspace);

        $leaveWorkspace->handle($request->user(), $workspace);

        return response()->noContent();
    }
}
