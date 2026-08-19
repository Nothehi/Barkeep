<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Queries\GetIterationTimeline;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Presentation\Http\Resources\IterationTimelineResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * A design cycle as it happened, on one axis.
 *
 * The endpoint behind the module's primary interaction. It gathers five kinds of record from four
 * tables and one other bounded context, which is exactly why it is a route of its own rather than
 * a field on the iteration: a header should not pay for the whole history every time it is drawn.
 */
class IterationTimelineController extends Controller
{
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        GetIterationTimeline $getTimeline,
    ): IterationTimelineResource {
        Gate::authorize('view', $iteration);

        return IterationTimelineResource::make($getTimeline->handle($iteration));
    }
}
