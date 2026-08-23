<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateGameRule;
use Modules\GameRules\Application\Commands\DeleteGameRule;
use Modules\GameRules\Application\Commands\ReorderGameRules;
use Modules\GameRules\Application\Commands\UpdateGameRule;
use Modules\GameRules\Application\Queries\GetRules;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreateGameRuleRequest;
use Modules\GameRules\Presentation\Http\Requests\ReorderGameRulesRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateGameRuleRequest;
use Modules\GameRules\Presentation\Http\Resources\GameRuleResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The rules themselves.
 *
 * The list is flat, at every depth, with child counts. A client assembles the tree
 * from `parent_rule_id` — one request rather than one per level, and a cycle in
 * the data cannot make the response recurse forever.
 */
class GameRuleController extends Controller
{
    /**
     * List the rules, flat, in reading order.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GetRules $getRules,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $ruleSet);

        return GameRuleResource::collection($getRules->handle($ruleSet));
    }

    /**
     * Write a rule down.
     */
    public function store(
        CreateGameRuleRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateGameRule $createRule,
    ): JsonResponse {
        $rule = $createRule->handle($request->user(), $ruleSet, $request->toData());

        return GameRuleResource::make($rule)->response()->setStatusCode(201);
    }

    /**
     * One rule, with everything that hangs off it.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GameRule $gameRule,
    ): GameRuleResource {
        Gate::authorize('view', $ruleSet);

        return GameRuleResource::make(
            $gameRule->load(['phase', 'children', 'requirements', 'effects', 'references.referencedRule']),
        );
    }

    /**
     * Reword a rule, retype it, move it in the tree, or retire it.
     */
    public function update(
        UpdateGameRuleRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GameRule $gameRule,
        UpdateGameRule $updateRule,
    ): GameRuleResource {
        return GameRuleResource::make($updateRule->handle($request->user(), $gameRule, $request->toData()));
    }

    /**
     * Remove a rule, promoting whatever sat under it.
     */
    public function destroy(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GameRule $gameRule,
        DeleteGameRule $deleteRule,
    ): JsonResponse {
        $deleteRule->handle($request->user(), $gameRule);

        return response()->json(status: 204);
    }

    /**
     * Put the rules into the order the designer dragged them into.
     */
    public function reorder(
        ReorderGameRulesRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ReorderGameRules $reorder,
        GetRules $getRules,
    ): AnonymousResourceCollection {
        $reorder->handle($request->user(), $ruleSet, $request->orderedIds());

        return GameRuleResource::collection($getRules->handle($ruleSet));
    }
}
