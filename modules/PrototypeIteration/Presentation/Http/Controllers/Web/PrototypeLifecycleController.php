<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\ArchivePrototype;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Presentation\Http\Requests\ArchivePrototypeRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Putting a prototype away for good, from the screen.
 *
 * A POST to a named action rather than a status field, because archival cannot be undone. The
 * flash message says what happened rather than that it succeeded, because the reader is about to
 * find the prototype read-only and should know why.
 */
class PrototypeLifecycleController extends Controller
{
    public function archive(
        ArchivePrototypeRequest $request,
        Workspace $workspace,
        Game $game,
        Prototype $prototype,
        ArchivePrototype $archivePrototype,
    ): RedirectResponse {
        $archivePrototype->handle($request->user(), $prototype);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Prototype archived. Its versions and iterations stay readable.'),
        ]);

        return back();
    }
}
