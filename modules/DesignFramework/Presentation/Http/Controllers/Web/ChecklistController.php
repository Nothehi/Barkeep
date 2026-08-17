<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\DesignFramework\Application\Commands\CreateChecklist;
use Modules\DesignFramework\Application\Commands\ReorderChecklist;
use Modules\DesignFramework\Application\Commands\UpdateChecklist;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Presentation\Http\Requests\Content\CreateChecklistRequest;
use Modules\DesignFramework\Presentation\Http\Requests\Content\UpdateChecklistRequest;
use Modules\DesignFramework\Presentation\Http\Requests\ReorderRequest;

/**
 * The framework builder's readiness gates.
 *
 * Three actions, all of them writes, all of them refused on a published edition by the one ability
 * the whole builder is arranged around. Nothing here renders a screen: the builder is a single page
 * served by `FrameworkVersionController`, and every write comes back to it as a redirect so the
 * reloaded page is what the server actually stored rather than something the client spliced in.
 */
class ChecklistController extends Controller
{
    /**
     * Add one to the edition.
     */
    public function store(
        CreateChecklistRequest $request,
        Framework $framework,
        FrameworkVersion $version,
        CreateChecklist $create,
    ): RedirectResponse {
        $create->handle($request->user(), $version, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Added.')]);

        return back();
    }

    /**
     * Change one.
     */
    public function update(
        UpdateChecklistRequest $request,
        Framework $framework,
        FrameworkVersion $version,
        Checklist $checklist,
        UpdateChecklist $update,
    ): RedirectResponse {
        $update->handle($request->user(), $checklist, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Saved.')]);

        return back();
    }

    /**
     * Move one among its siblings.
     *
     * A POST rather than a PATCH of a position field, because the position is not an attribute a
     * client sets — it is allocated by `ContentSequencer`, which rewrites the whole sibling set so the
     * result is always contiguous.
     */
    public function reorder(
        ReorderRequest $request,
        Framework $framework,
        FrameworkVersion $version,
        Checklist $checklist,
        ReorderChecklist $reorder,
    ): RedirectResponse {
        $reorder->handle($request->user(), $checklist, $request->position());

        return back();
    }
}
