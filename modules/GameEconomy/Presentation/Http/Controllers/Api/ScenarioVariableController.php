<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\RemoveScenarioVariable;
use Modules\GameEconomy\Application\Commands\SetScenarioVariable;
use Modules\GameEconomy\Application\Queries\GetScenarioVariables;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\GameEconomy\Domain\Models\ScenarioVariable;
use Modules\GameEconomy\Presentation\Http\Requests\ConfigurationChangeRequest;
use Modules\GameEconomy\Presentation\Http\Requests\SetScenarioVariableRequest;
use Modules\GameEconomy\Presentation\Http\Resources\ScenarioVariableResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The values a hypothetical states differently.
 *
 * `store` sets or replaces, because a scenario states one value per variable and
 * two rows would be two answers with no way to choose between them. Nothing here
 * writes to the base variable — the override is a row in a different table, so
 * there is no path from these endpoints that could reach it.
 */
class ScenarioVariableController extends Controller
{
    /**
     * List the values this hypothetical states differently.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        BalanceScenario $scenario,
        GetScenarioVariables $getOverrides,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $profile);

        return ScenarioVariableResource::collection($getOverrides->handle($scenario));
    }

    /**
     * State a value differently under this hypothetical.
     */
    public function store(
        SetScenarioVariableRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        BalanceScenario $scenario,
        SetScenarioVariable $setOverride,
    ): JsonResponse {
        $override = $setOverride->handle($request->user(), $scenario, $request->toData());

        return ScenarioVariableResource::make($override)
            ->response()
            ->setStatusCode($override->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Stop this hypothetical stating a value differently.
     */
    public function destroy(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        BalanceScenario $scenario,
        ScenarioVariable $override,
        RemoveScenarioVariable $removeOverride,
    ): JsonResponse {
        $removeOverride->handle($request->user(), $override);

        return response()->json(status: 204);
    }
}
