<?php

namespace Modules\GameDesign\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Modules\GameDesign\Application\Commands\ArchiveMechanic;
use Modules\GameDesign\Domain\Models\Mechanic;

/**
 * Retiring a term from the vocabulary.
 *
 * Its own controller and its own route, rather than a status field on the
 * update form, because retiring a word is irreversible and touches every game
 * that claimed it. A move with those consequences should not be one field value
 * away from a rename.
 */
class MechanicArchiveController extends Controller
{
    /**
     * Retire it.
     */
    public function store(Request $request, Mechanic $mechanic, ArchiveMechanic $archive): RedirectResponse
    {
        Gate::authorize('archive', $mechanic);

        $archive->handle($request->user(), $mechanic);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mechanic retired.')]);

        return back();
    }
}
