<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreatePhaseTransition;
use Modules\GameRules\Application\Commands\DeletePhaseTransition;
use Modules\GameRules\Application\Commands\UpdatePhaseTransition;
use Modules\GameRules\Application\Queries\GetPhaseTransitions;
use Modules\GameRules\Domain\Models\PhaseTransition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreatePhaseTransitionRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdatePhaseTransitionRequest;
use Modules\GameRules\Presentation\Http\Resources\PhaseTransitionResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * How play moves between phases.
 *
 * Both ends of every edge are resolved through the rule set, so a transition can
 * never span two rule systems.
 */
class PhaseTransitionController extends Controller
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
        GetPhaseTransitions $query,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $ruleSet);

        return PhaseTransitionResource::collection($query->handle($ruleSet));
    }

    /**
     * Add one.
     */
    public function store(
        CreatePhaseTransitionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreatePhaseTransition $create,
    ): JsonResponse {
        $record = $create->handle($request->user(), $ruleSet, $request->toData());

        return PhaseTransitionResource::make($record)->response()->setStatusCode(201);
    }

    /**
     * Change one.
     */
    public function update(
        UpdatePhaseTransitionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        PhaseTransition $transition,
        UpdatePhaseTransition $update,
    ): PhaseTransitionResource {
        return PhaseTransitionResource::make($update->handle($request->user(), $transition, $request->toData()));
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
        PhaseTransition $transition,
        DeletePhaseTransition $delete,
    ): JsonResponse {
        $delete->handle($request->user(), $transition);

        return response()->json(status: 204);
    }
}
