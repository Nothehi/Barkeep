<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateRuleReference;
use Modules\GameRules\Application\Commands\DeleteRuleReference;
use Modules\GameRules\Application\Queries\GetRuleReferences;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\RuleReference;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreateRuleReferenceRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Resources\RuleReferenceResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * How the rules relate to one another.
 *
 * Listed at the rule set — the whole graph is the useful read — and written under
 * the rule doing the referring, which is what makes the pairing check possible.
 */
class RuleReferenceController extends Controller
{
    /**
     * Every relationship in the rule set.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GetRuleReferences $getReferences,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $ruleSet);

        return RuleReferenceResource::collection($getReferences->handle($ruleSet));
    }

    /**
     * Say that one rule depends on, modifies, overrides or excepts another.
     */
    public function store(
        CreateRuleReferenceRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GameRule $gameRule,
        CreateRuleReference $createReference,
    ): JsonResponse {
        $reference = $createReference->handle($request->user(), $gameRule, $request->toData());

        return RuleReferenceResource::make($reference)->response()->setStatusCode(201);
    }

    /**
     * Withdraw the claim that two rules are connected.
     */
    public function destroy(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GameRule $gameRule,
        RuleReference $reference,
        DeleteRuleReference $deleteReference,
    ): JsonResponse {
        $deleteReference->handle($request->user(), $reference);

        return response()->json(status: 204);
    }
}
