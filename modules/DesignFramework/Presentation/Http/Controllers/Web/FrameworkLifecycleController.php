<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Modules\DesignFramework\Application\Commands\ArchiveFramework;
use Modules\DesignFramework\Application\Commands\PublishFramework;
use Modules\DesignFramework\Domain\Models\Framework;

/**
 * Moving a methodology through its lifecycle.
 *
 * POSTs to named actions rather than a PATCH of the status field, because they are actions with rules
 * rather than an editable attribute — and because archiving cannot be undone, which is a poor fit for
 * anything that looks like a form field.
 */
class FrameworkLifecycleController extends Controller
{
    /**
     * Make the framework visible to every designer on the platform.
     */
    public function publish(Request $request, Framework $framework, PublishFramework $publish): RedirectResponse
    {
        Gate::authorize('publish', $framework);

        $publish->handle($request->user(), $framework);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Framework published.')]);

        return back();
    }

    /**
     * Retire the framework.
     */
    public function archive(Request $request, Framework $framework, ArchiveFramework $archive): RedirectResponse
    {
        Gate::authorize('archive', $framework);

        $archive->handle($request->user(), $framework);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Framework archived.')]);

        return to_route('frameworks.index');
    }
}
