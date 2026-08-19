<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\CreateBalanceVariable;
use Modules\GameEconomy\Application\Commands\DeleteBalanceVariable;
use Modules\GameEconomy\Application\Commands\UpdateBalanceVariable;
use Modules\GameEconomy\Application\Queries\GetBalanceVariables;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceVariable;
use Modules\GameEconomy\Presentation\Http\Requests\ConfigurationChangeRequest;
use Modules\GameEconomy\Presentation\Http\Requests\CreateBalanceVariableRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateBalanceVariableRequest;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceVariableResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The numbers a configuration exposes for tuning.
 *
 * The update route is what the variable table's inline editing writes through,
 * which is why its request accepts every field optionally: a cell sending only
 * `value` must not be refused for omitting the name.
 */
class BalanceVariableController extends Controller
{
    /**
     * List the configuration's tunable numbers.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        GetBalanceVariables $getVariables,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $profile);

        return BalanceVariableResource::collection($getVariables->handle($profile));
    }

    /**
     * Expose a number for tuning.
     */
    public function store(
        CreateBalanceVariableRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        CreateBalanceVariable $createVariable,
    ): JsonResponse {
        $variable = $createVariable->handle($request->user(), $profile, $request->toData());

        return BalanceVariableResource::make($variable)->response()->setStatusCode(201);
    }

    /**
     * Change a tunable number.
     */
    public function update(
        UpdateBalanceVariableRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        BalanceVariable $variable,
        UpdateBalanceVariable $updateVariable,
    ): BalanceVariableResource {
        $updateVariable->handle($request->user(), $variable, $request->toData());

        return BalanceVariableResource::make($variable->load(['resource', 'action']));
    }

    /**
     * Remove a tunable number, and every scenario override of it.
     */
    public function destroy(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        BalanceVariable $variable,
        DeleteBalanceVariable $deleteVariable,
    ): JsonResponse {
        $deleteVariable->handle($request->user(), $variable);

        return response()->json(status: 204);
    }
}
