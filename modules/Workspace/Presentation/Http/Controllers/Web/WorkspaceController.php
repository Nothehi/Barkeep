<?php

namespace Modules\Workspace\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Workspace\Application\Commands\CreateWorkspace;
use Modules\Workspace\Application\Commands\LeaveWorkspace;
use Modules\Workspace\Application\Queries\GetUserWorkspaces;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Requests\CreateWorkspaceRequest;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * The workspace screens.
 *
 * These render pages and hand off to the same application commands and form
 * requests the JSON API uses, so there is one implementation of every rule
 * and two ways to reach it.
 */
class WorkspaceController extends Controller
{
    /**
     * Show the workspaces the caller belongs to.
     */
    public function index(Request $request, GetUserWorkspaces $getUserWorkspaces): Response
    {
        return Inertia::render('workspaces/index', [
            'workspaces' => WorkspaceResource::collection(
                $getUserWorkspaces->handle($request->user()),
            ),
        ]);
    }

    /**
     * Show the workspace creation form.
     */
    public function create(): Response
    {
        Gate::authorize('create', Workspace::class);

        return Inertia::render('workspaces/create');
    }

    /**
     * Open a new workspace and go straight into it.
     */
    public function store(CreateWorkspaceRequest $request, CreateWorkspace $createWorkspace): RedirectResponse
    {
        $workspace = $createWorkspace->handle($request->user(), $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace created.')]);

        return to_route('workspaces.show', $workspace);
    }

    /**
     * Show a single workspace.
     */
    public function show(Workspace $workspace): Response
    {
        Gate::authorize('view', $workspace);

        return Inertia::render('workspaces/show', [
            'workspace' => WorkspaceResource::make($workspace->loadCount('members')),
        ]);
    }

    /**
     * Leave the workspace.
     */
    public function leave(Request $request, Workspace $workspace, LeaveWorkspace $leaveWorkspace): RedirectResponse
    {
        Gate::authorize('leave', $workspace);

        $leaveWorkspace->handle($request->user(), $workspace);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('You have left the workspace.')]);

        return to_route('workspaces.index');
    }
}
