<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\DesignFramework\Application\Commands\EvaluateCriterion;
use Modules\DesignFramework\Application\Queries\GetCriterionEvaluations;
use Modules\DesignFramework\Application\Queries\GetGameFramework;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Presentation\Http\Requests\EvaluateCriterionRequest;
use Modules\DesignFramework\Presentation\Http\Resources\CriterionEvaluationResource;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * A game's assessments of itself against its framework's criteria.
 *
 * The criterion arrives as a route segment and is resolved through the framework version the game
 * adopted, so a criterion from another edition 404s before this runs. That is section 22's separation
 * enforced by the router: an evaluation belongs to the game's adoption, and it can only ever point at
 * a question that adoption actually asks.
 */
class CriterionEvaluationController extends Controller
{
    /**
     * List what this game has said about itself.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GetGameFramework $getGameFramework,
        GetCriterionEvaluations $getEvaluations,
    ): AnonymousResourceCollection {
        Gate::authorize('viewForGame', [GameFramework::class, $game]);

        $adoption = $getGameFramework->handle($game);

        abort_if($adoption === null, 404, __('This game is not following a design framework.'));

        Gate::authorize('view', $adoption);

        return CriterionEvaluationResource::collection($getEvaluations->handle($adoption));
    }

    /**
     * Grade the game against one criterion.
     *
     * Idempotent by design: evaluating again replaces the standing answer, because a criterion asks
     * how the design is now.
     */
    public function store(
        EvaluateCriterionRequest $request,
        Workspace $workspace,
        Game $game,
        DesignCriterion $criterion,
        EvaluateCriterion $evaluateCriterion,
    ): CriterionEvaluationResource {
        $evaluation = $evaluateCriterion->handle(
            $request->user(),
            $request->adoption(),
            $criterion,
            $request->toData(),
        );

        return CriterionEvaluationResource::make($evaluation);
    }
}
