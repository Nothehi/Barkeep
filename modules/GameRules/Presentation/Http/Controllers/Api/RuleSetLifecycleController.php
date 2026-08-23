<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\ActivateRuleSet;
use Modules\GameRules\Application\Commands\ArchiveRuleSet;
use Modules\GameRules\Application\Commands\CloneRuleSet;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CloneRuleSetRequest;
use Modules\GameRules\Presentation\Http\Requests\RuleSetLifecycleRequest;
use Modules\GameRules\Presentation\Http\Resources\RuleSetResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Moving a rule set through its life.
 *
 * Three named actions rather than a PATCH of a status field, because all three
 * have rules a field assignment could not express: activating retires whichever
 * set was in play and is refused while the validator reports errors, archiving
 * cannot be undone, and cloning creates a whole second rule system.
 */
class RuleSetLifecycleController extends Controller
{
    /**
     * Put a rule system into play.
     */
    public function activate(
        RuleSetLifecycleRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ActivateRuleSet $activate,
    ): RuleSetResource {
        return RuleSetResource::make($activate->handle($request->user(), $ruleSet));
    }

    /**
     * Put a rule system away for good.
     */
    public function archive(
        RuleSetLifecycleRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ArchiveRuleSet $archive,
    ): RuleSetResource {
        return RuleSetResource::make($archive->handle($request->user(), $ruleSet));
    }

    /**
     * Copy a rule system into a fresh draft.
     *
     * Answers 201 with the *new* rule set, because the caller almost always wants
     * to start editing it — which is the whole reason cloning exists.
     */
    public function clone(
        CloneRuleSetRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CloneRuleSet $cloneRuleSet,
    ): JsonResponse {
        $clone = $cloneRuleSet->handle($request->user(), $ruleSet, $request->toData());

        return RuleSetResource::make($clone)->response()->setStatusCode(201);
    }
}
