<?php

namespace Modules\Workspace\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\Workspace\Application\Commands\CreateWorkspace;
use Modules\Workspace\Application\Commands\UpdateWorkspace;
use Modules\Workspace\Application\Queries\GetUserWorkspaces;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Requests\CreateWorkspaceRequest;
use Modules\Workspace\Presentation\Http\Requests\UpdateWorkspaceRequest;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * The workspaces an account belongs to.
 *
 * Every action here is authorized before it runs, and the listing is scoped
 * to membership rather than filtered afterwards, so there is no request that
 * can return a workspace the caller does not belong to.
 */
class WorkspaceController extends Controller
{
    /**
     * List the workspaces the caller belongs to.
     */
    public function index(Request $request, GetUserWorkspaces $getUserWorkspaces): AnonymousResourceCollection
    {
        return WorkspaceResource::collection(
            $getUserWorkspaces->handle($request->user()),
        );
    }

    /**
     * Open a new workspace, owned by the caller.
     */
    public function store(CreateWorkspaceRequest $request, CreateWorkspace $createWorkspace): JsonResponse
    {
        $workspace = $createWorkspace->handle($request->user(), $request->toData());

        return WorkspaceResource::make($workspace->loadCount('members'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a single workspace.
     */
    public function show(Request $request, Workspace $workspace): WorkspaceResource
    {
        Gate::authorize('view', $workspace);

        return WorkspaceResource::make($workspace->loadCount('members'));
    }

    /**
     * Update a workspace's own settings.
     */
    public function update(UpdateWorkspaceRequest $request, Workspace $workspace, UpdateWorkspace $updateWorkspace): WorkspaceResource
    {
        $updateWorkspace->handle($request->user(), $workspace, $request->toData());

        return WorkspaceResource::make($workspace->loadCount('members'));
    }
}
