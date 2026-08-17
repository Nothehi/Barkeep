<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Modules\DesignFramework\Application\Commands\ArchiveFrameworkVersion;
use Modules\DesignFramework\Application\Commands\PublishFrameworkVersion;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;

/**
 * Freezing and retiring editions.
 *
 * Publishing is the most consequential button in the module: it ends the version's editable life and
 * opens it to adoption. There is no way back, which is why it is a named action with its own
 * confirmation rather than a status dropdown.
 */
class FrameworkVersionLifecycleController extends Controller
{
    /**
     * Freeze the edition and release it.
     */
    public function publish(
        Request $request,
        Framework $framework,
        FrameworkVersion $version,
        PublishFrameworkVersion $publish,
    ): RedirectResponse {
        Gate::authorize('publishVersion', $version);

        $publish->handle($request->user(), $version);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Version :label published. It is now read-only.', ['label' => $version->label()]),
        ]);

        return back();
    }

    /**
     * Retire the edition.
     */
    public function archive(
        Request $request,
        Framework $framework,
        FrameworkVersion $version,
        ArchiveFrameworkVersion $archive,
    ): RedirectResponse {
        Gate::authorize('archiveVersion', $version);

        $archive->handle($request->user(), $version);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Version archived.')]);

        return to_route('frameworks.show', $framework);
    }
}
