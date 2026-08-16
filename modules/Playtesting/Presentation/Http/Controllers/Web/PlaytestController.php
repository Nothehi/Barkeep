<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Presentation\Http\Resources\GameResource;
use Modules\GameDesign\Presentation\Http\Resources\GameVersionResource;
use Modules\Playtesting\Application\Commands\CreatePlaytest;
use Modules\Playtesting\Application\Commands\UpdatePlaytest;
use Modules\Playtesting\Application\Queries\GetPlaytests;
use Modules\Playtesting\Application\Queries\GetPlaytestSummary;
use Modules\Playtesting\Application\Queries\GetSessions;
use Modules\Playtesting\Domain\Enums\ObservationCategory;
use Modules\Playtesting\Domain\Enums\PlaytestParticipantRole;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\ValueObjects\FeedbackRating;
use Modules\Playtesting\Infrastructure\Authorization\PlaytestPermissions;
use Modules\Playtesting\Infrastructure\GameDesign\GameCatalogue;
use Modules\Playtesting\Presentation\Http\Requests\CreatePlaytestRequest;
use Modules\Playtesting\Presentation\Http\Requests\PlaytestFilterRequest;
use Modules\Playtesting\Presentation\Http\Requests\UpdatePlaytestRequest;
use Modules\Playtesting\Presentation\Http\Resources\PlaytestMetricsResource;
use Modules\Playtesting\Presentation\Http\Resources\PlaytestResource;
use Modules\Playtesting\Presentation\Http\Resources\PlaytestSessionResource;
use Modules\Playtesting\Presentation\Http\Resources\PlaytestSummaryResource;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * The playtest screens.
 *
 * These render pages and hand off to the same application commands, form
 * requests and queries the JSON API uses, so there is one implementation of
 * every rule and two ways to reach it.
 */
class PlaytestController extends Controller
{
    /**
     * Show the game's playtests.
     *
     * The filters are echoed back so the screen can render what it is
     * currently showing without keeping its own copy of the query string, and
     * the option lists come from the enums rather than being restated in
     * TypeScript.
     */
    public function index(
        PlaytestFilterRequest $request,
        Workspace $workspace,
        Game $game,
        GetPlaytests $getPlaytests,
        GameCatalogue $catalogue,
    ): Response {
        $filters = $request->toFilters();

        return Inertia::render('playtests/index', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),
            'playtests' => PlaytestSummaryResource::collection($getPlaytests->handle($game, $filters)),
            'versions' => GameVersionResource::collection($catalogue->versionsOf($game)),
            'filters' => [
                'search' => $filters->search,
                'status' => $filters->status?->value,
            ],
            'options' => $this->options(),
            'can' => [
                'create' => app(PlaytestPermissions::class)->canCreateFor($request->user(), $game),
            ],
        ]);
    }

    /**
     * Plan a playtest and go straight into it.
     */
    public function store(
        CreatePlaytestRequest $request,
        Workspace $workspace,
        Game $game,
        CreatePlaytest $createPlaytest,
    ): RedirectResponse {
        $playtest = $createPlaytest->handle($request->user(), $game, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Playtest planned.')]);

        return to_route('playtests.show', [$workspace, $game, $playtest]);
    }

    /**
     * Show a playtest: what it set out to find out, and what it has found.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        GetSessions $getSessions,
        GetPlaytestSummary $getSummary,
        GameCatalogue $catalogue,
    ): Response {
        Gate::authorize('view', $playtest);

        return Inertia::render('playtests/show', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),
            'playtest' => PlaytestResource::make(
                $playtest->load(['version', 'creator'])->loadCount('sessions'),
            ),
            'sessions' => PlaytestSessionResource::collection($getSessions->handle($playtest)),
            'summary' => PlaytestMetricsResource::make($getSummary->handle($playtest)),
            'versions' => GameVersionResource::collection($catalogue->versionsOf($game)),
            'options' => $this->options(),
        ]);
    }

    /**
     * Change a playtest's plan, its conclusion, or both.
     */
    public function update(
        UpdatePlaytestRequest $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        UpdatePlaytest $updatePlaytest,
    ): RedirectResponse {
        $updatePlaytest->handle($request->user(), $playtest, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Playtest updated.')]);

        return back();
    }

    /**
     * The values the playtesting screens let somebody choose between.
     *
     * Sent from the server so that the labels, the ordering and the sets
     * themselves have one definition. A client that hard-coded them would be a
     * second opinion waiting to go stale — and the observation categories in
     * particular are the list most likely to change, since the framework
     * system will eventually own them.
     *
     * @return array{statuses: list<array{value: string, label: string}>, categories: list<array{value: string, label: string, description: string}>, roles: list<array{value: string, label: string, description: string}>, rating_scale: list<int>}
     */
    private function options(): array
    {
        return [
            'statuses' => array_map(
                fn (PlaytestStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                PlaytestStatus::cases(),
            ),
            'categories' => array_map(
                fn (ObservationCategory $category): array => [
                    'value' => $category->value,
                    'label' => $category->label(),
                    'description' => $category->description(),
                ],
                ObservationCategory::cases(),
            ),
            'roles' => array_map(
                fn (PlaytestParticipantRole $role): array => [
                    'value' => $role->value,
                    'label' => $role->label(),
                    'description' => $role->description(),
                ],
                PlaytestParticipantRole::cases(),
            ),
            'rating_scale' => FeedbackRating::scale(),
        ];
    }
}
