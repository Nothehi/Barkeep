<?php

namespace Modules\GameDesign\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\GameDesign\Application\Commands\ArchiveGame;
use Modules\GameDesign\Application\Commands\UpdateGame;
use Modules\GameDesign\Application\Queries\GetDesignRecord;
use Modules\GameDesign\Application\Queries\GetMechanics;
use Modules\GameDesign\Domain\Enums\Complexity;
use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Presentation\Http\Requests\UpdateGameRequest;
use Modules\GameDesign\Presentation\Http\Resources\DesignRecordResource;
use Modules\GameDesign\Presentation\Http\Resources\GameResource;
use Modules\GameDesign\Presentation\Http\Resources\MechanicResource;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * A game's settings screen and the changes it can make.
 *
 * Archival lives here rather than beside the other lifecycle actions because
 * it is not one of them: it cannot be undone, and it ends the game's editable
 * life for everybody in the workspace.
 */
class GameSettingsController extends Controller
{
    /**
     * Show the game's settings.
     */
    public function edit(
        Request $request,
        Workspace $workspace,
        Game $game,
        GetDesignRecord $getDesignRecord,
        GetMechanics $getMechanics,
    ): Response {
        Gate::authorize('view', $game);

        $record = $getDesignRecord->handle($game);

        return Inertia::render('games/settings', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),

            /*
             * Null when the designer has decided nothing, which is most games.
             * The screen renders an empty form from that rather than being sent
             * a record full of nulls, so "not decided" stays distinguishable
             * from "decided to leave blank".
             */
            'design_record' => $record === null ? null : DesignRecordResource::make($record),

            /*
             * The whole vocabulary, because the mechanics picker cannot be
             * filled in from the client and the list is small enough to send.
             * Retired terms are excluded: a designer must not be offered a word
             * the platform has withdrawn, and the ones they already claimed
             * arrive on the record itself.
             */
            'mechanics' => MechanicResource::collection($getMechanics->handle()),

            'options' => [
                'design_phases' => array_map(
                    fn (DesignPhase $phase): array => [
                        'value' => $phase->value,
                        'label' => $phase->label(),
                        'description' => $phase->description(),
                        'position' => $phase->position(),
                    ],
                    DesignPhase::cases(),
                ),
                'complexities' => array_map(
                    fn (Complexity $complexity): array => [
                        'value' => $complexity->value,
                        'label' => $complexity->label(),
                        'description' => $complexity->description(),
                        'position' => $complexity->position(),
                    ],
                    Complexity::cases(),
                ),
            ],
        ]);
    }

    /**
     * Save the game's name, address and description.
     */
    public function update(
        UpdateGameRequest $request,
        Workspace $workspace,
        Game $game,
        UpdateGame $updateGame,
    ): RedirectResponse {
        $updateGame->handle($request->user(), $game, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Game updated.')]);

        /** Renaming may have moved the address, so redirect to where it is now. */
        return to_route('games.settings.edit', [$workspace, $game->fresh()]);
    }

    /**
     * Put the game away.
     */
    public function archive(
        Request $request,
        Workspace $workspace,
        Game $game,
        ArchiveGame $archiveGame,
    ): RedirectResponse {
        Gate::authorize('archive', $game);

        $archiveGame->handle($request->user(), $game);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Game archived.')]);

        return to_route('games.show', [$workspace, $game]);
    }
}
