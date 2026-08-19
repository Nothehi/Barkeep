<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\CreateNextGameVersion;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreateNextGameVersionRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Cutting the next design version of a game from a completed cycle.
 *
 * The button section 48 asks for, and the point at which the design loop closes — by a person
 * choosing to, on a cycle that has already concluded. Completing an iteration does not do this,
 * and must not: most cycles do not produce a new design state, and a platform that cut one per
 * cycle would turn the version numbers into a count of button presses.
 *
 * Lands on the game's version screen rather than staying on the iteration, because cutting a
 * version is the end of one cycle and the start of the next — and the next thing a designer does
 * is describe the new version.
 */
class IterationGameVersionController extends Controller
{
    public function store(
        CreateNextGameVersionRequest $request,
        Workspace $workspace,
        Game $game,
        Iteration $iteration,
        CreateNextGameVersion $createVersion,
    ): RedirectResponse {
        $version = $createVersion->handle(
            $request->user(),
            $iteration,
            $request->versionName(),
            $request->versionDescription(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Game version :label created.', ['label' => $version->label()]),
        ]);

        return to_route('games.versions.show', [$workspace, $game, $version]);
    }
}
