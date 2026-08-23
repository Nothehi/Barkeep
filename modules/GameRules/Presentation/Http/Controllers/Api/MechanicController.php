<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateMechanic;
use Modules\GameRules\Application\Commands\DeleteMechanic;
use Modules\GameRules\Application\Commands\UpdateMechanic;
use Modules\GameRules\Application\Queries\GetMechanics;
use Modules\GameRules\Domain\Models\RuleMechanic;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreateMechanicRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateMechanicRequest;
use Modules\GameRules\Presentation\Http\Resources\MechanicResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The mechanisms a rule system says it uses.
 *
 * Not GameDesign's mechanic catalogue, which is the shared vocabulary of design
 * terms. These are one studio's words for the mechanisms in one game.
 */
class MechanicController extends Controller
{
    /**
     * List them.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GetMechanics $query,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $ruleSet);

        return MechanicResource::collection($query->handle($ruleSet));
    }

    /**
     * Add one.
     */
    public function store(
        CreateMechanicRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateMechanic $create,
    ): JsonResponse {
        $record = $create->handle($request->user(), $ruleSet, $request->toData());

        return MechanicResource::make($record)->response()->setStatusCode(201);
    }

    /**
     * Change one.
     */
    public function update(
        UpdateMechanicRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleMechanic $ruleMechanic,
        UpdateMechanic $update,
    ): MechanicResource {
        return MechanicResource::make($update->handle($request->user(), $ruleMechanic, $request->toData()));
    }

    /**
     * Remove one.
     */
    public function destroy(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleMechanic $ruleMechanic,
        DeleteMechanic $delete,
    ): JsonResponse {
        $delete->handle($request->user(), $ruleMechanic);

        return response()->json(status: 204);
    }
}
