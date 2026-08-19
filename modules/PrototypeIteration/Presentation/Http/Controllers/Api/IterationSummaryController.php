<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Queries\GetIterationSummary;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Presentation\Http\Resources\IterationSummaryResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * What a design cycle produced, as figures.
 *
 * Its own endpoint rather than fields on the iteration, because the counts cost several aggregate
 * queries plus a pass through Playtesting — and the iteration header is drawn on every screen in
 * this part of the application, while the summary panel is drawn on one.
 */
class IterationSummaryController extends Controller
{
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        GetIterationSummary $getSummary,
    ): IterationSummaryResource {
        Gate::authorize('view', $iteration);

        return IterationSummaryResource::make($getSummary->handle($iteration));
    }
}
