<?php

namespace Modules\Playtesting\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Playtesting\Application\Queries\GetPlaytestSummary;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Presentation\Http\Resources\PlaytestMetricsResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What a playtest has produced.
 *
 * A read model rather than an analytics endpoint. Every figure is counted at
 * request time from the playtest's own rows, so there is no stored total that
 * can drift from what it describes.
 */
class PlaytestSummaryController extends Controller
{
    /**
     * Show the playtest's metrics.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        Playtest $playtest,
        GetPlaytestSummary $getSummary,
    ): PlaytestMetricsResource {
        Gate::authorize('view', $playtest);

        return PlaytestMetricsResource::make($getSummary->handle($playtest));
    }
}
