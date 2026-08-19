<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\ArchiveBalanceScenario;
use Modules\GameEconomy\Application\Commands\CreateBalanceScenario;
use Modules\GameEconomy\Application\Commands\UpdateBalanceScenario;
use Modules\GameEconomy\Application\Queries\GetBalanceScenarios;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceScenario;
use Modules\GameEconomy\Presentation\Http\Requests\ConfigurationChangeRequest;
use Modules\GameEconomy\Presentation\Http\Requests\CreateBalanceScenarioRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateBalanceScenarioRequest;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceScenarioResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The hypotheticals a configuration is read under.
 */
class BalanceScenarioController extends Controller
{
    /**
     * List the configuration's hypotheticals.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        GetBalanceScenarios $getScenarios,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $profile);

        return BalanceScenarioResource::collection($getScenarios->handle($profile));
    }

    /**
     * Name a situation to read the economy under.
     */
    public function store(
        CreateBalanceScenarioRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        CreateBalanceScenario $createScenario,
    ): JsonResponse {
        $scenario = $createScenario->handle($request->user(), $profile, $request->toData());

        return BalanceScenarioResource::make($scenario)->response()->setStatusCode(201);
    }

    /**
     * Show one hypothetical and the values it states differently.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        BalanceScenario $scenario,
    ): BalanceScenarioResource {
        Gate::authorize('view', $profile);

        return BalanceScenarioResource::make(
            $scenario->load(['creator', 'overrides.variable']),
        );
    }

    /**
     * Rename a hypothetical, or say the studio is now reading against it.
     */
    public function update(
        UpdateBalanceScenarioRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        BalanceScenario $scenario,
        UpdateBalanceScenario $updateScenario,
    ): BalanceScenarioResource {
        $updateScenario->handle($request->user(), $scenario, $request->toData(), $request->toStatus());

        return BalanceScenarioResource::make($scenario->load('creator'));
    }

    /**
     * Put a hypothetical away.
     */
    public function archive(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        BalanceScenario $scenario,
        ArchiveBalanceScenario $archiveScenario,
    ): BalanceScenarioResource {
        $archiveScenario->handle($request->user(), $scenario);

        return BalanceScenarioResource::make($scenario->load('creator'));
    }
}
