<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\DesignFramework\Application\Queries\GetChecklistProgress;
use Modules\DesignFramework\Application\Queries\GetCriterionEvaluations;
use Modules\DesignFramework\Application\Queries\GetFrameworkPhases;
use Modules\DesignFramework\Application\Queries\GetGameFramework;
use Modules\DesignFramework\Application\Queries\GetGameFrameworkProgress;
use Modules\DesignFramework\Application\Queries\GetPhaseChecklists;
use Modules\DesignFramework\Application\Queries\GetPhaseCriteria;
use Modules\DesignFramework\Application\Queries\GetPhasePractices;
use Modules\DesignFramework\Application\Queries\GetPhasePrinciples;
use Modules\DesignFramework\Application\Queries\GetPhasePrompts;
use Modules\DesignFramework\Application\Queries\GetPracticeCompletions;
use Modules\DesignFramework\Application\Queries\GetPromptResponses;
use Modules\DesignFramework\Domain\Enums\CriterionRating;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Infrastructure\GameDesign\DesignFacts;
use Modules\DesignFramework\Presentation\Http\Resources\ChecklistProgressResource;
use Modules\DesignFramework\Presentation\Http\Resources\CriterionEvaluationResource;
use Modules\DesignFramework\Presentation\Http\Resources\CriterionResource;
use Modules\DesignFramework\Presentation\Http\Resources\FrameworkProgressResource;
use Modules\DesignFramework\Presentation\Http\Resources\GameFrameworkResource;
use Modules\DesignFramework\Presentation\Http\Resources\PhaseResource;
use Modules\DesignFramework\Presentation\Http\Resources\PracticeCompletionResource;
use Modules\DesignFramework\Presentation\Http\Resources\PracticeResource;
use Modules\DesignFramework\Presentation\Http\Resources\PrincipleResource;
use Modules\DesignFramework\Presentation\Http\Resources\PromptResource;
use Modules\DesignFramework\Presentation\Http\Resources\PromptResponseResource;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Presentation\Http\Resources\GameResource;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * One phase of a game's framework: the working screen.
 *
 * The busiest read in the module, and the one that justifies the module's shape. It shows framework
 * content and a studio's own state side by side — the criteria and this game's grades, the practices
 * and this game's completions, the checklists and this game's ticks, the prompts and this game's
 * answers — while keeping them in separate collections all the way to the client. A designer sees one
 * page; the data never conflates methodology with progress.
 *
 * The phase is resolved by slug through the version the game adopted, rather than through a version
 * named in the URL. That is section 19 in practice: a game on v1 addressing `core-loop` gets v1's core
 * loop phase for as long as the game exists, whatever v2 renamed or split.
 */
class GameFrameworkPhaseController extends Controller
{
    /**
     * Show one phase of the methodology, and what this game has done in it.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        DesignPhaseDefinition $phase,
        GetGameFramework $getGameFramework,
        GetFrameworkPhases $getPhases,
        GetGameFrameworkProgress $getProgress,
        GetPhasePrinciples $getPrinciples,
        GetPhaseCriteria $getCriteria,
        GetPhasePractices $getPractices,
        GetPhasePrompts $getPrompts,
        GetPhaseChecklists $getChecklists,
        GetCriterionEvaluations $getEvaluations,
        GetPracticeCompletions $getCompletions,
        GetChecklistProgress $getChecklistProgress,
        GetPromptResponses $getResponses,
        DesignFacts $facts,
    ): Response {
        Gate::authorize('viewForGame', [GameFramework::class, $game]);

        $adoption = $getGameFramework->handle($game);

        abort_if($adoption === null, 404, __('This game is not following a design framework.'));

        Gate::authorize('view', $adoption);

        $version = $adoption->version;

        abort_if($version === null, 404);

        /*
         * The binding resolved the phase through this game's adopted version, so it is already the
         * right edition's. What it did not do is check visibility — the builder shows draft phases to
         * their author and this screen must not, so the refusal belongs here rather than in a binder
         * both chains share.
         */
        if (! $phase->isVisibleToDesigners()) {
            throw (new ModelNotFoundException)->setModel(DesignPhaseDefinition::class, [$phase->slug]);
        }

        $checklists = $getChecklists->handle($version, $phase);

        return Inertia::render('games/framework-phase', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game),
            'adoption' => GameFrameworkResource::make($adoption),
            'progress' => FrameworkProgressResource::make($getProgress->handle($adoption)),
            'phase' => PhaseResource::make($phase),
            'phases' => PhaseResource::collection($getPhases->handle($version)),

            'principles' => PrincipleResource::collection($getPrinciples->handle($version, $phase)),
            'criteria' => CriterionResource::collection($getCriteria->handle($version, $phase)),
            'practices' => PracticeResource::collection($getPractices->handle($version, $phase)),
            'prompts' => PromptResource::collection($getPrompts->handle($version, $phase)),

            /*
             * The studio's own state, kept in its own collections. The client joins them to the content
             * above by id, which is what stops a criterion from ever carrying somebody's grade.
             */
            'evaluations' => CriterionEvaluationResource::collection($getEvaluations->handle($adoption)),
            'completions' => PracticeCompletionResource::collection($getCompletions->handle($adoption)),
            'checklists' => ChecklistProgressResource::collection($getChecklistProgress->handle($adoption, $checklists)),
            'responses' => PromptResponseResource::collection($getResponses->handle($adoption)),

            /*
             * What this game has written down about its own design, as one map of
             * fact to whether it is recorded — plus where to go and record it.
             *
             * Kept separate from the criteria and the checklist items above, for
             * the same reason the evaluations are: the content is the
             * methodology's and identical for every game, and this is one game's
             * answer. The client joins them by the fact key.
             */
            'design' => [
                'facts' => $facts->recordedMap($facts->recordFor($game)),
                'settings_url' => route('games.settings.edit', [$workspace, $game]),
            ],

            'options' => [
                'ratings' => array_map(
                    fn (CriterionRating $rating): array => [
                        'value' => $rating->value,
                        'label' => $rating->label(),
                        'description' => $rating->description(),
                    ],
                    CriterionRating::grades(),
                ),
            ],
        ]);
    }
}
