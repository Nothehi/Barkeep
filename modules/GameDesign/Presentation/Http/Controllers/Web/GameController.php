<?php

namespace Modules\GameDesign\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\GameDesign\Application\Commands\CreateGame;
use Modules\GameDesign\Application\Queries\GetGameDashboard;
use Modules\GameDesign\Application\Queries\GetGames;
use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Infrastructure\Authorization\GamePermissions;
use Modules\GameDesign\Presentation\Http\Requests\CreateGameRequest;
use Modules\GameDesign\Presentation\Http\Requests\GameFilterRequest;
use Modules\GameDesign\Presentation\Http\Resources\DesignRecordResource;
use Modules\GameDesign\Presentation\Http\Resources\GameResource;
use Modules\GameDesign\Presentation\Http\Resources\GameSummaryResource;
use Modules\GameDesign\Presentation\Http\Resources\GameVersionResource;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * The game screens.
 *
 * These render pages and hand off to the same application commands, form
 * requests and queries the JSON API uses, so there is one implementation of
 * every rule and two ways to reach it.
 */
class GameController extends Controller
{
    /**
     * Show the games in the workspace.
     *
     * The filters are echoed back so the screen can render what it is
     * currently showing without keeping its own copy of the query string, and
     * the option lists come from the enums rather than being restated in
     * TypeScript.
     */
    public function index(GameFilterRequest $request, Workspace $workspace, GetGames $getGames): Response
    {
        $filters = $request->toFilters();

        return Inertia::render('games/index', [
            'workspace' => WorkspaceResource::make($workspace),
            'games' => GameSummaryResource::collection($getGames->handle($workspace, $filters)),
            'filters' => [
                'search' => $filters->search?->value,
                'status' => $filters->status?->value,
                'design_phase' => $filters->designPhase?->value,
            ],
            'options' => $this->options(),
            'can' => [
                'create' => app(GamePermissions::class)->canCreateIn($request->user(), $workspace),
            ],
        ]);
    }

    /**
     * Start a new game and go straight into it.
     */
    public function store(CreateGameRequest $request, Workspace $workspace, CreateGame $createGame): RedirectResponse
    {
        $game = $createGame->handle($request->user(), $workspace, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Game created.')]);

        return to_route('games.show', [$workspace, $game]);
    }

    /**
     * Show a game's overview.
     */
    public function show(Request $request, Workspace $workspace, Game $game, GetGameDashboard $getDashboard): Response
    {
        Gate::authorize('view', $game);

        $dashboard = $getDashboard->handle($game);

        return Inertia::render('games/show', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),
            'dashboard' => [
                'versions_count' => $dashboard->versionCount,
                'latest_version' => $dashboard->latestVersion === null
                    ? null
                    : GameVersionResource::make($dashboard->latestVersion),

                /*
                 * Null when the designer has decided nothing, which is most
                 * games. The screen draws an invitation to record the first
                 * thing from that, rather than a summary full of dashes.
                 */
                'design_record' => $dashboard->designRecord === null
                    ? null
                    : DesignRecordResource::make($dashboard->designRecord),
            ],
            'options' => $this->options(),
        ]);
    }

    /**
     * The values the game screens let somebody choose between.
     *
     * Sent from the server so that the labels, the ordering and the set
     * itself have one definition. A client that hard-coded them would be a
     * second opinion waiting to go stale.
     *
     * @return array{statuses: list<array{value: string, label: string}>, design_phases: list<array{value: string, label: string, description: string, position: int}>}
     */
    private function options(): array
    {
        return [
            'statuses' => array_map(
                fn (GameStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                GameStatus::cases(),
            ),
            'design_phases' => array_map(
                fn (DesignPhase $phase): array => [
                    'value' => $phase->value,
                    'label' => $phase->label(),
                    'description' => $phase->description(),
                    'position' => $phase->position(),
                ],
                DesignPhase::cases(),
            ),
        ];
    }
}
