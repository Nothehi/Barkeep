<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateRuleAction;
use Modules\GameRules\Application\Commands\DeleteRuleAction;
use Modules\GameRules\Application\Commands\ReorderRuleActions;
use Modules\GameRules\Application\Commands\UpdateRuleAction;
use Modules\GameRules\Application\Queries\GetRuleActions;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreateRuleActionRequest;
use Modules\GameRules\Presentation\Http\Requests\ReorderRuleActionsRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateRuleActionRequest;
use Modules\GameRules\Presentation\Http\Resources\RuleActionResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The things a player may do.
 *
 * The list returns counts and the detail route returns every line, for the same
 * reason every list in the platform does: an actions screen draws "2 requirements,
 * 3 effects" per row and would otherwise cost two queries per action.
 *
 * What an action *costs* is not here and never will be. That is GameEconomy's, and
 * this module holds a handle to it rather than a copy — see section 16 of the
 * module brief.
 */
class RuleActionController extends Controller
{
    /**
     * List the actions.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GetRuleActions $getActions,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $ruleSet);

        return RuleActionResource::collection($getActions->handle($ruleSet));
    }

    /**
     * Declare something a player may do.
     */
    public function store(
        CreateRuleActionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateRuleAction $createAction,
    ): JsonResponse {
        $action = $createAction->handle($request->user(), $ruleSet, $request->toData());

        return RuleActionResource::make($action)->response()->setStatusCode(201);
    }

    /**
     * One action, with its requirements and effects.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleAction $ruleAction,
    ): RuleActionResource {
        Gate::authorize('view', $ruleSet);

        return RuleActionResource::make($ruleAction->load(['phase', 'requirements', 'effects']));
    }

    /**
     * Rename an action, move it to another phase, or wire it to the economy.
     */
    public function update(
        UpdateRuleActionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleAction $ruleAction,
        UpdateRuleAction $updateAction,
    ): RuleActionResource {
        return RuleActionResource::make(
            $updateAction->handle($request->user(), $ruleAction, $request->toData()),
        );
    }

    /**
     * Remove an action, and everything it required and did.
     */
    public function destroy(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleAction $ruleAction,
        DeleteRuleAction $deleteAction,
    ): JsonResponse {
        $deleteAction->handle($request->user(), $ruleAction);

        return response()->json(status: 204);
    }

    /**
     * Put the actions into the order the designer arranged them in.
     */
    public function reorder(
        ReorderRuleActionsRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ReorderRuleActions $reorder,
        GetRuleActions $getActions,
    ): AnonymousResourceCollection {
        $reorder->handle($request->user(), $ruleSet, $request->orderedIds());

        return RuleActionResource::collection($getActions->handle($ruleSet));
    }
}
