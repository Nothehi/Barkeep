<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateRuleSet;
use Modules\GameRules\Application\Commands\UpdateRuleSet;
use Modules\GameRules\Application\Queries\GetRuleSets;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreateRuleSetRequest;
use Modules\GameRules\Presentation\Http\Requests\RuleSetFilterRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateRuleSetRequest;
use Modules\GameRules\Presentation\Http\Resources\RuleSetResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The rule systems written for one design state.
 *
 * Nested under `versions/{version}` rather than exposed at a flat
 * `/rule-sets/{id}`, which is the platform's settled position rather than a
 * routing preference: reaching a rule set without its version would mean looking
 * the parent up *from* the child, and that reverse lookup is exactly what turns a
 * guessed id into cross-workspace access. Every segment here is resolved through
 * the one before it, so a rule set from somebody else's game 404s before a handler
 * runs.
 */
class RuleSetController extends Controller
{
    /**
     * List the rule systems written for this design state.
     */
    public function index(
        RuleSetFilterRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        GetRuleSets $getRuleSets,
    ): AnonymousResourceCollection {
        return RuleSetResource::collection($getRuleSets->handle($version, $request->toFilters()));
    }

    /**
     * Start writing a game's rules down.
     */
    public function store(
        CreateRuleSetRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        CreateRuleSet $createRuleSet,
    ): JsonResponse {
        $ruleSet = $createRuleSet->handle($request->user(), $version, $request->toData());

        return RuleSetResource::make($ruleSet)->response()->setStatusCode(201);
    }

    /**
     * One rule set, with counts rather than contents.
     *
     * The contents have their own endpoints, and the analysis endpoint returns all
     * of them at once for a client that wants the whole thing.
     */
    public function show(
        RuleSetFilterRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
    ): RuleSetResource {
        return RuleSetResource::make(
            $ruleSet->load(['version', 'creator'])
                ->loadCount(['rules', 'mechanics', 'phases', 'actions', 'conditions']),
        );
    }

    /**
     * Correct the rule set's own title or summary.
     */
    public function update(
        UpdateRuleSetRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        UpdateRuleSet $updateRuleSet,
    ): RuleSetResource {
        return RuleSetResource::make(
            $updateRuleSet->handle($request->user(), $ruleSet, $request->toData()),
        );
    }
}
