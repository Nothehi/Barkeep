<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\DesignFramework\Application\Commands\AssignFrameworkToGame;
use Modules\DesignFramework\Application\Queries\GetFrameworkPhases;
use Modules\DesignFramework\Application\Queries\GetFrameworks;
use Modules\DesignFramework\Application\Queries\GetGameFramework;
use Modules\DesignFramework\Application\Queries\GetGameFrameworkProgress;
use Modules\DesignFramework\Domain\Enums\CriterionRating;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Infrastructure\Authorization\GameFrameworkPermissions;
use Modules\DesignFramework\Presentation\Http\Requests\AssignFrameworkRequest;
use Modules\DesignFramework\Presentation\Http\Resources\FrameworkProgressResource;
use Modules\DesignFramework\Presentation\Http\Resources\FrameworkResource;
use Modules\DesignFramework\Presentation\Http\Resources\GameFrameworkResource;
use Modules\DesignFramework\Presentation\Http\Resources\PhaseResource;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Presentation\Http\Resources\GameResource;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * A game's framework screen.
 *
 * One page that answers two different questions depending on whether the game follows a methodology
 * yet. Before: here are the published frameworks, pick one. After: here is where you are, phase by
 * phase.
 *
 * Serving both from one route is deliberate. "Adopt a framework" is not a separate destination a
 * designer navigates to — it is what this screen offers when there is nothing to show yet, and making
 * it its own page would leave a dead link on every game that already has one.
 */
class GameFrameworkController extends Controller
{
    /**
     * Show the game's framework, or the choice of frameworks.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GetGameFramework $getGameFramework,
        GetGameFrameworkProgress $getProgress,
        GetFrameworkPhases $getPhases,
        GetFrameworks $getFrameworks,
    ): Response {
        Gate::authorize('viewForGame', [GameFramework::class, $game]);

        $adoption = $getGameFramework->handle($game);
        $version = $adoption?->version;

        return Inertia::render('games/framework', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game),
            'adoption' => $adoption === null ? null : GameFrameworkResource::make($adoption),
            'progress' => $adoption === null ? null : FrameworkProgressResource::make($getProgress->handle($adoption)),
            'phases' => $version === null
                ? PhaseResource::collection([])
                : PhaseResource::collection($getPhases->handle($version)),

            /*
             * The catalogue is only fetched when there is a choice to make. A game already following a
             * methodology has no use for the list, and sending it would invite a screen to offer a
             * switch the module does not implement — see section 19: migration is a deliberate future
             * operation, not a dropdown.
             */
            'available' => $adoption !== null
                ? FrameworkResource::collection([])
                : FrameworkResource::collection($getFrameworks->handle()),

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
            'can' => [
                'assign' => app(GameFrameworkPermissions::class)->canAssignFor($request->user(), $game),
            ],
        ]);
    }

    /**
     * Adopt a framework version for this game.
     */
    public function store(
        AssignFrameworkRequest $request,
        Workspace $workspace,
        Game $game,
        AssignFrameworkToGame $assignFramework,
    ): RedirectResponse {
        $version = FrameworkVersion::query()
            ->with('framework')
            ->findOrFail($request->frameworkVersionId());

        $assignFramework->handle($request->user(), $game, $version);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Framework adopted.')]);

        return to_route('games.framework.show', [$workspace, $game]);
    }
}
