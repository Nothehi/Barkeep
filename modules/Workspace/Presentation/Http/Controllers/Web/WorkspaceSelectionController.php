<?php

namespace Modules\Workspace\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Workspace\Application\Queries\GetUserWorkspaces;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Infrastructure\Session\ActiveWorkspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * The step between signing in and the app itself.
 *
 * An account can belong to several workspaces, and almost everything inside
 * the app is about exactly one of them, so the first screen after sign in
 * asks which. An account that belongs to none is sent to create one instead of
 * being shown an empty list — there is only one thing it could do from here.
 */
class WorkspaceSelectionController extends Controller
{
    /**
     * Ask which workspace to work in.
     */
    public function show(Request $request, GetUserWorkspaces $getUserWorkspaces): Response|RedirectResponse
    {
        $workspaces = $getUserWorkspaces->handle($request->user());

        if ($workspaces->isEmpty()) {
            return to_route('workspaces.create');
        }

        return Inertia::render('workspaces/select', [
            'workspaces' => WorkspaceResource::collection($workspaces),
        ]);
    }

    /**
     * Take the choice and go into the app.
     *
     * The workspace comes from the URL and is authorized against the policy,
     * so choosing one is exactly as restricted as opening it.
     */
    public function store(Workspace $workspace, ActiveWorkspace $activeWorkspace): RedirectResponse
    {
        Gate::authorize('view', $workspace);

        $activeWorkspace->remember($workspace);

        return to_route('dashboard');
    }
}
