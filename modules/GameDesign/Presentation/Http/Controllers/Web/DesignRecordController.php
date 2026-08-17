<?php

namespace Modules\GameDesign\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Application\Commands\UpdateDesignRecord;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Presentation\Http\Requests\UpdateDesignRecordRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Recording what has been decided about a game's design.
 *
 * Its own endpoint rather than a widening of the game update, because the two
 * are different acts with different meanings. Renaming a project is
 * administration; deciding it is for two to four players in forty-five minutes
 * is design work, and it is the answer a methodology's factual criteria read.
 *
 * The write comes back as a redirect so the settings screen reloads with what
 * the server actually stored — which matters here more than usual, because the
 * ranges are normalised on the way in: a designer who fills in only the first
 * player-count box gets a fixed count back rather than a half-filled range.
 */
class DesignRecordController extends Controller
{
    /**
     * Save the design record.
     */
    public function update(
        UpdateDesignRecordRequest $request,
        Workspace $workspace,
        Game $game,
        UpdateDesignRecord $updateDesignRecord,
    ): RedirectResponse {
        $updateDesignRecord->handle($request->user(), $game, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Design saved.')]);

        return back();
    }
}
