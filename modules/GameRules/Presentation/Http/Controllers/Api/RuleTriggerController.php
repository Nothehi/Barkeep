<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateRuleTrigger;
use Modules\GameRules\Application\Commands\DeleteRuleTrigger;
use Modules\GameRules\Application\Commands\UpdateRuleTrigger;
use Modules\GameRules\Application\Queries\GetTriggers;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\Models\RuleTrigger;
use Modules\GameRules\Presentation\Http\Requests\CreateRuleTriggerRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateRuleTriggerRequest;
use Modules\GameRules\Presentation\Http\Resources\RuleTriggerResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The things a rule system says happen automatically.
 *
 * Recorded, never fired.
 */
class RuleTriggerController extends Controller
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
        GetTriggers $query,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $ruleSet);

        return RuleTriggerResource::collection($query->handle($ruleSet));
    }

    /**
     * Add one.
     */
    public function store(
        CreateRuleTriggerRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateRuleTrigger $create,
    ): JsonResponse {
        $record = $create->handle($request->user(), $ruleSet, $request->toData());

        return RuleTriggerResource::make($record)->response()->setStatusCode(201);
    }

    /**
     * Change one.
     */
    public function update(
        UpdateRuleTriggerRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleTrigger $trigger,
        UpdateRuleTrigger $update,
    ): RuleTriggerResource {
        return RuleTriggerResource::make($update->handle($request->user(), $trigger, $request->toData()));
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
        RuleTrigger $trigger,
        DeleteRuleTrigger $delete,
    ): JsonResponse {
        $delete->handle($request->user(), $trigger);

        return response()->json(status: 204);
    }
}
