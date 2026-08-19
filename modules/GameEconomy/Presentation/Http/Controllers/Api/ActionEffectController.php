<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\AddActionEffect;
use Modules\GameEconomy\Application\Commands\RemoveActionEffect;
use Modules\GameEconomy\Application\Commands\UpdateActionEffect;
use Modules\GameEconomy\Application\Queries\GetActionEffects;
use Modules\GameEconomy\Domain\Models\ActionEffect;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Presentation\Http\Requests\AddActionEffectRequest;
use Modules\GameEconomy\Presentation\Http\Requests\ConfigurationChangeRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateActionEffectRequest;
use Modules\GameEconomy\Presentation\Http\Resources\ActionEffectResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What an action does beyond moving resources.
 */
class ActionEffectController extends Controller
{
    /**
     * List the action's effects.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        GetActionEffects $getEffects,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $profile);

        return ActionEffectResource::collection($getEffects->handle($economyAction));
    }

    /**
     * Record something the action does beyond resources.
     */
    public function store(
        AddActionEffectRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        AddActionEffect $addEffect,
    ): JsonResponse {
        $effect = $addEffect->handle($request->user(), $economyAction, $request->toData());

        return ActionEffectResource::make($effect)->response()->setStatusCode(201);
    }

    /**
     * Change one of the action's effects.
     */
    public function update(
        UpdateActionEffectRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        ActionEffect $effect,
        UpdateActionEffect $updateEffect,
    ): ActionEffectResource {
        $updateEffect->handle($request->user(), $effect, $request->toData());

        return ActionEffectResource::make($effect);
    }

    /**
     * Remove one of the action's effects.
     */
    public function destroy(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        ActionEffect $effect,
        RemoveActionEffect $removeEffect,
    ): JsonResponse {
        $removeEffect->handle($request->user(), $effect);

        return response()->json(status: 204);
    }
}
