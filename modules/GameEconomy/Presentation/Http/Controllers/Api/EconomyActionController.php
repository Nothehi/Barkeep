<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\CreateEconomyAction;
use Modules\GameEconomy\Application\Commands\DeleteEconomyAction;
use Modules\GameEconomy\Application\Commands\UpdateEconomyAction;
use Modules\GameEconomy\Application\Queries\GetEconomyActions;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Presentation\Http\Requests\ConfigurationChangeRequest;
use Modules\GameEconomy\Presentation\Http\Requests\CreateEconomyActionRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateEconomyActionRequest;
use Modules\GameEconomy\Presentation\Http\Resources\EconomyActionResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The actions a configuration declares.
 *
 * The list returns counts and the detail route returns every line. An actions
 * screen draws "3 costs, 1 reward" per row and would otherwise cost three
 * queries per action; the action page needs all of them and would otherwise need
 * three more requests to draw one screen.
 */
class EconomyActionController extends Controller
{
    /**
     * List the configuration's actions.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        GetEconomyActions $getActions,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $profile);

        return EconomyActionResource::collection($getActions->handle($profile));
    }

    /**
     * Declare something that moves the economy.
     */
    public function store(
        CreateEconomyActionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        CreateEconomyAction $createAction,
    ): JsonResponse {
        $action = $createAction->handle($request->user(), $profile, $request->toData());

        return EconomyActionResource::make($action)->response()->setStatusCode(201);
    }

    /**
     * Show one action and everything it does.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
    ): EconomyActionResource {
        Gate::authorize('view', $profile);

        return EconomyActionResource::make(
            $economyAction->load(['costs.resource', 'rewards.resource', 'effects']),
        );
    }

    /**
     * Rename an action or change what it is for.
     */
    public function update(
        UpdateEconomyActionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        UpdateEconomyAction $updateAction,
    ): EconomyActionResource {
        $updateAction->handle($request->user(), $economyAction, $request->toData());

        return EconomyActionResource::make(
            $economyAction->load(['costs.resource', 'rewards.resource', 'effects']),
        );
    }

    /**
     * Remove an action, and everything it did with it.
     */
    public function destroy(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        DeleteEconomyAction $deleteAction,
    ): JsonResponse {
        $deleteAction->handle($request->user(), $economyAction);

        return response()->json(status: 204);
    }
}
