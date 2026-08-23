<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateRuleEffect;
use Modules\GameRules\Application\Commands\DeleteRuleEffect;
use Modules\GameRules\Application\Commands\UpdateRuleEffect;
use Modules\GameRules\Application\Queries\GetEffects;
use Modules\GameRules\Domain\Models\RuleEffect;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreateRuleEffectRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\GameRules\Presentation\Http\Requests\UpdateRuleEffectRequest;
use Modules\GameRules\Presentation\Http\Resources\RuleEffectResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What happens when a rule or an action resolves.
 *
 * Descriptions, not instructions. Nothing in this module executes an effect.
 */
class RuleEffectController extends Controller
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
        GetEffects $query,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $ruleSet);

        return RuleEffectResource::collection($query->handle($ruleSet));
    }

    /**
     * Add one.
     */
    public function store(
        CreateRuleEffectRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CreateRuleEffect $create,
    ): JsonResponse {
        $record = $create->handle($request->user(), $ruleSet, $request->toData());

        return RuleEffectResource::make($record)->response()->setStatusCode(201);
    }

    /**
     * Change one.
     */
    public function update(
        UpdateRuleEffectRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        RuleEffect $ruleEffect,
        UpdateRuleEffect $update,
    ): RuleEffectResource {
        return RuleEffectResource::make($update->handle($request->user(), $ruleEffect, $request->toData()));
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
        RuleEffect $ruleEffect,
        DeleteRuleEffect $delete,
    ): JsonResponse {
        $delete->handle($request->user(), $ruleEffect);

        return response()->json(status: 204);
    }
}
