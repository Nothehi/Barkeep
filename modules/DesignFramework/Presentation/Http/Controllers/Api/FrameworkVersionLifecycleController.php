<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\DesignFramework\Application\Commands\ArchiveFrameworkVersion;
use Modules\DesignFramework\Application\Commands\PublishFrameworkVersion;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Presentation\Http\Resources\FrameworkVersionResource;

/**
 * Freezing and retiring editions over the JSON surface.
 *
 * Publishing is the most consequential operation in the module: it ends a version's editable life and
 * opens it to adoption, and there is no route back because there is no transition back. A POST to a named
 * action rather than a PATCH of the status field, so nothing about it looks like setting a value.
 *
 * Both actions live on one controller because they are the two ends of one edition's life and share their
 * whole authorization story — the policy answers `publishVersion` and `archiveVersion` from the same
 * matrix.
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
    ): FrameworkVersionResource {
        Gate::authorize('publishVersion', $version);

        $publish->handle($request->user(), $version);

        return FrameworkVersionResource::make($version->loadCount(['phases', 'adoptions']));
    }

    /**
     * Retire the edition.
     *
     * Games already following it keep reading it and keep everything they recorded — the database will not
     * let the row go while anything points at it. What this stops is new adoptions.
     */
    public function archive(
        Request $request,
        Framework $framework,
        FrameworkVersion $version,
        ArchiveFrameworkVersion $archive,
    ): FrameworkVersionResource {
        Gate::authorize('archiveVersion', $version);

        $archive->handle($request->user(), $version);

        return FrameworkVersionResource::make($version->loadCount(['phases', 'adoptions']));
    }
}
