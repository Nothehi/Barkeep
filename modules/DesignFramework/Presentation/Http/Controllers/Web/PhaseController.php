<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\DesignFramework\Application\Commands\CreatePhase;
use Modules\DesignFramework\Application\Commands\ReorderPhase;
use Modules\DesignFramework\Application\Commands\UpdatePhase;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Presentation\Http\Requests\CreatePhaseRequest;
use Modules\DesignFramework\Presentation\Http\Requests\ReorderRequest;
use Modules\DesignFramework\Presentation\Http\Requests\UpdatePhaseRequest;

/**
 * The framework builder's stages.
 *
 * Phases are the spine of the builder: everything else is filed under one. Reordering them is
 * therefore the reorder that matters most, and it is a POST to a named action rather than a PATCH of a
 * position field — the position is allocated by `ContentSequencer`, which rewrites the whole set so the
 * result is always contiguous.
 */
class PhaseController extends Controller
{
    /**
     * Add a stage to the edition.
     */
    public function store(
        CreatePhaseRequest $request,
        Framework $framework,
        FrameworkVersion $version,
        CreatePhase $createPhase,
    ): RedirectResponse {
        $createPhase->handle($request->user(), $version, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Phase added.')]);

        return back();
    }

    /**
     * Change a stage's name, description or status.
     */
    public function update(
        UpdatePhaseRequest $request,
        Framework $framework,
        FrameworkVersion $version,
        DesignPhaseDefinition $phase,
        UpdatePhase $updatePhase,
    ): RedirectResponse {
        $updatePhase->handle($request->user(), $phase, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Phase updated.')]);

        return back();
    }

    /**
     * Move a stage to a different place in the arc.
     */
    public function reorder(
        ReorderRequest $request,
        Framework $framework,
        FrameworkVersion $version,
        DesignPhaseDefinition $phase,
        ReorderPhase $reorderPhase,
    ): RedirectResponse {
        $reorderPhase->handle($request->user(), $phase, $request->position());

        return back();
    }
}
