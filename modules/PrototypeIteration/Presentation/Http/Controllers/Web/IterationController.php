<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Presentation\Http\Resources\GameResource;
use Modules\GameDesign\Presentation\Http\Resources\GameVersionResource;
use Modules\PrototypeIteration\Application\Commands\CreateIteration;
use Modules\PrototypeIteration\Application\Commands\UpdateIteration;
use Modules\PrototypeIteration\Application\Queries\GetDecisionEvidence;
use Modules\PrototypeIteration\Application\Queries\GetDecisions;
use Modules\PrototypeIteration\Application\Queries\GetDesignChanges;
use Modules\PrototypeIteration\Application\Queries\GetExperiments;
use Modules\PrototypeIteration\Application\Queries\GetIterationPlaytests;
use Modules\PrototypeIteration\Application\Queries\GetIterations;
use Modules\PrototypeIteration\Application\Queries\GetIterationSummary;
use Modules\PrototypeIteration\Application\Queries\GetIterationTimeline;
use Modules\PrototypeIteration\Application\Services\PrototypeCatalogue;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Infrastructure\Authorization\IterationPermissions;
use Modules\PrototypeIteration\Infrastructure\GameDesign\GameCatalogue;
use Modules\PrototypeIteration\Infrastructure\Playtesting\PlaytestEvidence;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Web\Concerns\ProvidesDesignVocabulary;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreateIterationRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\IterationFilterRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\UpdateIterationRequest;
use Modules\PrototypeIteration\Presentation\Http\Resources\DecisionEvidenceResource;
use Modules\PrototypeIteration\Presentation\Http\Resources\DesignChangeResource;
use Modules\PrototypeIteration\Presentation\Http\Resources\DesignDecisionResource;
use Modules\PrototypeIteration\Presentation\Http\Resources\DesignExperimentResource;
use Modules\PrototypeIteration\Presentation\Http\Resources\IterationCardResource;
use Modules\PrototypeIteration\Presentation\Http\Resources\IterationResource;
use Modules\PrototypeIteration\Presentation\Http\Resources\IterationSummaryResource;
use Modules\PrototypeIteration\Presentation\Http\Resources\IterationTimelineResource;
use Modules\PrototypeIteration\Presentation\Http\Resources\PlaytestReferenceResource;
use Modules\PrototypeIteration\Presentation\Http\Resources\PrototypeVersionResource;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * The iteration screens.
 *
 * These render pages and hand off to the same application commands, form requests and queries the
 * JSON API uses, so there is one implementation of every rule and two ways to reach it.
 *
 * The detail screen is the busiest in the application, and it is loaded in one response rather than
 * assembled from a dozen client-side fetches. That is deliberate: a design cycle is read as a
 * whole — the objective, then what changed, then what was tested, then what was decided — and a
 * page that filled in section by section would be unreadable during the second it took.
 */
class IterationController extends Controller
{
    use ProvidesDesignVocabulary;

    /**
     * Show the game's design cycles.
     */
    public function index(
        IterationFilterRequest $request,
        Workspace $workspace,
        Game $game,
        GetIterations $getIterations,
        GameCatalogue $games,
        PrototypeCatalogue $prototypes,
    ): Response {
        $filters = $request->toFilters();

        return Inertia::render('iterations/index', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),
            'iterations' => IterationCardResource::collection($getIterations->handle($game, $filters)),

            /*
             * Both pickers the "plan an iteration" dialog needs. They are sent from the list screen as well as
             * from the detail one because the dialog lives here — a cycle is planned from the list, before
             * there is an iteration to open.
             */
            'versions' => GameVersionResource::collection($games->versionsOf($game)),
            'prototype_versions' => PrototypeVersionResource::collection(
                $prototypes->selectableVersionsOf($game),
            ),
            'filters' => [
                'search' => $filters->search,
                'status' => $filters->status?->value,
                'outcome' => $filters->outcome?->value,
                'prototype' => $filters->prototypeId,
            ],
            'options' => $this->iterationVocabulary(),
            'can' => [
                'create' => app(IterationPermissions::class)->canCreateFor($request->user(), $game),
            ],
        ]);
    }

    /**
     * Plan a cycle and go straight into it.
     */
    public function store(
        CreateIterationRequest $request,
        Workspace $workspace,
        Game $game,
        CreateIteration $createIteration,
    ): RedirectResponse {
        $iteration = $createIteration->handle($request->user(), $game, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Iteration planned.')]);

        return to_route('iterations.show', [$workspace, $game, $iteration]);
    }

    /**
     * Show a design cycle in full.
     *
     * Everything the design loop consists of, in the order section 40 sets out: what we were trying
     * to change, why, what we tested, what happened, what we decided. The timeline is the same
     * material on one axis, and it is sent alongside rather than instead — a reader arriving to
     * find one thing uses the sections, and a reader catching up on a cycle uses the line.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        GetDesignChanges $getChanges,
        GetExperiments $getExperiments,
        GetDecisions $getDecisions,
        GetDecisionEvidence $getEvidence,
        GetIterationPlaytests $getPlaytests,
        GetIterationSummary $getSummary,
        GetIterationTimeline $getTimeline,
        GameCatalogue $games,
        PrototypeCatalogue $prototypes,
        PlaytestEvidence $playtesting,
    ): Response {
        Gate::authorize('view', $iteration);

        $decisions = $getDecisions->handle($iteration);

        return Inertia::render('iterations/show', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),
            'iteration' => IterationResource::make(
                $iteration
                    ->load(['version', 'prototypeVersion.prototype', 'creator'])
                    ->loadCount(['changes', 'experiments', 'decisions', 'playtestLinks']),
            ),
            'changes' => DesignChangeResource::collection($getChanges->handle($iteration)),
            'experiments' => DesignExperimentResource::collection($getExperiments->handle($iteration)),
            'decisions' => DesignDecisionResource::collection($decisions),

            /*
             * The citations, resolved, keyed by the decision they support.
             *
             * Sent alongside the decisions rather than nested inside them because resolving a citation means
             * reading the cited words live from Playtesting — that is a query's job, not a resource's, and
             * folding it into `DesignDecisionResource` would make every list of decisions anywhere pay for it.
             *
             * Keyed rather than flat so the client can render each decision with its own evidence without
             * grouping an array on every render.
             */
            'evidence' => $decisions
                ->mapWithKeys(fn (DesignDecision $decision): array => [
                    $decision->getKey() => DecisionEvidenceResource::collection(
                        $getEvidence->handle($decision),
                    )->resolve(),
                ])
                ->all(),
            'playtests' => PlaytestReferenceResource::collection($getPlaytests->handle($iteration)),
            'summary' => IterationSummaryResource::make($getSummary->handle($iteration)),
            'timeline' => IterationTimelineResource::make($getTimeline->handle($iteration)),

            /*
             * The pickers. The playtest list comes from Playtesting through this module's adapter,
             * unfiltered — the screen knows which links it already holds and greys those out, and
             * filtering here would mean the adapter needed to know about the iteration asking.
             */
            'versions' => GameVersionResource::collection($games->versionsOf($game)),
            'prototype_versions' => PrototypeVersionResource::collection(
                $prototypes->selectableVersionsOf($game),
            ),
            'available_playtests' => $playtesting->selectableFor($game)->all(),

            'options' => $this->iterationVocabulary(),
        ]);
    }

    /**
     * Change a cycle's plan.
     */
    public function update(
        UpdateIterationRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        UpdateIteration $updateIteration,
    ): RedirectResponse {
        $updateIteration->handle($request->user(), $iteration, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Iteration updated.')]);

        return back();
    }
}
