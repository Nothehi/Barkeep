<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\CreateDesignChange;
use Modules\PrototypeIteration\Application\Commands\DeleteDesignChange;
use Modules\PrototypeIteration\Application\Commands\UpdateDesignChange;
use Modules\PrototypeIteration\Domain\Models\DesignChange;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreateDesignChangeRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\DeleteDesignChangeRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\UpdateDesignChangeRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Recording design changes from the iteration screen.
 *
 * Every action redirects back rather than answering with JSON, so the reloaded page brings the new
 * change, the recomputed summary and the updated timeline in one round trip. Splicing a change into
 * a local list would leave three parts of the same screen holding different ideas of what the cycle
 * contains.
 */
class IterationChangeController extends Controller
{
    /**
     * Record something the designer deliberately changed.
     */
    public function store(
        CreateDesignChangeRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        CreateDesignChange $createChange,
    ): RedirectResponse {
        $createChange->handle($request->user(), $iteration, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Change recorded.')]);

        return back();
    }

    /**
     * Reword a change.
     */
    public function update(
        UpdateDesignChangeRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        DesignChange $change,
        UpdateDesignChange $updateChange,
    ): RedirectResponse {
        $updateChange->handle($request->user(), $change, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Change updated.')]);

        return back();
    }

    /**
     * Remove a change entered by mistake.
     */
    public function destroy(
        DeleteDesignChangeRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        DesignChange $change,
        DeleteDesignChange $deleteChange,
    ): RedirectResponse {
        $deleteChange->handle($request->user(), $change);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Change removed.')]);

        return back();
    }
}
