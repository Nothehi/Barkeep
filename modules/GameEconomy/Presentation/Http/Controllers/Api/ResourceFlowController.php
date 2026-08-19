<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\CreateResourceFlow;
use Modules\GameEconomy\Application\Commands\DeleteResourceFlow;
use Modules\GameEconomy\Application\Commands\UpdateResourceFlow;
use Modules\GameEconomy\Application\Queries\GetResourceFlows;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\ResourceFlow;
use Modules\GameEconomy\Presentation\Http\Requests\ConfigurationChangeRequest;
use Modules\GameEconomy\Presentation\Http\Requests\CreateResourceFlowRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateResourceFlowRequest;
use Modules\GameEconomy\Presentation\Http\Resources\ResourceFlowResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The declared movements of a configuration's resources.
 */
class ResourceFlowController extends Controller
{
    /**
     * List the configuration's declared movements.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        GetResourceFlows $getFlows,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $profile);

        return ResourceFlowResource::collection($getFlows->handle($profile));
    }

    /**
     * Declare a way a resource moves.
     */
    public function store(
        CreateResourceFlowRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        CreateResourceFlow $createFlow,
    ): JsonResponse {
        $flow = $createFlow->handle($request->user(), $profile, $request->toData());

        return ResourceFlowResource::make($flow)->response()->setStatusCode(201);
    }

    /**
     * Retune a declared movement.
     */
    public function update(
        UpdateResourceFlowRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        ResourceFlow $flow,
        UpdateResourceFlow $updateFlow,
    ): ResourceFlowResource {
        $updateFlow->handle($request->user(), $flow, $request->toData());

        return ResourceFlowResource::make($flow->load('resource'));
    }

    /**
     * Remove a declared movement.
     */
    public function destroy(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        ResourceFlow $flow,
        DeleteResourceFlow $deleteFlow,
    ): JsonResponse {
        $deleteFlow->handle($request->user(), $flow);

        return response()->json(status: 204);
    }
}
