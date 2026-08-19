<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\AttachPlaytestToIteration;
use Modules\PrototypeIteration\Application\Commands\DetachPlaytestFromIteration;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\Models\IterationPlaytest;
use Modules\PrototypeIteration\Presentation\Http\Requests\AttachPlaytestRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\DetachPlaytestRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Attaching and detaching playtests from the iteration screen.
 *
 * Neither method takes a `Playtest`, which is the module's boundary showing up in a controller
 * signature: attaching resolves an id through this module's Playtesting adapter, and detaching
 * addresses the link. The screen's own playtest picker is populated by the same adapter, so the
 * client never talks to Playtesting either — section 46.
 */
class IterationPlaytestController extends Controller
{
    /**
     * Attach a playtest to this cycle.
     */
    public function store(
        AttachPlaytestRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        AttachPlaytestToIteration $attachPlaytest,
    ): RedirectResponse {
        $attachPlaytest->handle($request->user(), $iteration, $request->playtestId());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Playtest attached.')]);

        return back();
    }

    /**
     * Remove a playtest that did not test this cycle after all.
     */
    public function destroy(
        DetachPlaytestRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        IterationPlaytest $link,
        DetachPlaytestFromIteration $detachPlaytest,
    ): RedirectResponse {
        $detachPlaytest->handle($request->user(), $link);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Playtest detached.')]);

        return back();
    }
}
