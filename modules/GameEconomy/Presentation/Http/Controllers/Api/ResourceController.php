<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\CreateResourceType;
use Modules\GameEconomy\Application\Commands\DeleteResourceType;
use Modules\GameEconomy\Application\Commands\UpdateResourceType;
use Modules\GameEconomy\Application\Queries\GetResources;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Presentation\Http\Requests\ConfigurationChangeRequest;
use Modules\GameEconomy\Presentation\Http\Requests\CreateResourceRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateResourceRequest;
use Modules\GameEconomy\Presentation\Http\Resources\ResourceTypeResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The resources a configuration declares.
 */
class ResourceController extends Controller
{
    /**
     * List the configuration's resources, in the designer's own order.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        GetResources $getResources,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $profile);

        return ResourceTypeResource::collection($getResources->handle($profile));
    }

    /**
     * Declare something players hold and spend.
     */
    public function store(
        CreateResourceRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        CreateResourceType $createResource,
    ): JsonResponse {
        $resource = $createResource->handle($request->user(), $profile, $request->toData());

        return ResourceTypeResource::make($resource)->response()->setStatusCode(201);
    }

    /**
     * Show one resource.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        ResourceType $resourceType,
    ): ResourceTypeResource {
        Gate::authorize('view', $profile);

        return ResourceTypeResource::make($resourceType->loadCount(['flows', 'costs', 'rewards']));
    }

    /**
     * Retune a resource.
     */
    public function update(
        UpdateResourceRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        ResourceType $resourceType,
        UpdateResourceType $updateResource,
    ): ResourceTypeResource {
        $updateResource->handle($request->user(), $resourceType, $request->toData());

        return ResourceTypeResource::make($resourceType);
    }

    /**
     * Remove a resource nothing depends on.
     */
    public function destroy(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        ResourceType $resourceType,
        DeleteResourceType $deleteResource,
    ): JsonResponse {
        $deleteResource->handle($request->user(), $resourceType);

        return response()->json(status: 204);
    }
}
