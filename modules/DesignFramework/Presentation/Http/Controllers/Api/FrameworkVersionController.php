<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\DesignFramework\Application\Commands\CreateFrameworkVersion;
use Modules\DesignFramework\Application\Commands\UpdateFrameworkVersion;
use Modules\DesignFramework\Application\Queries\GetFrameworkVersions;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Presentation\Http\Requests\CreateFrameworkVersionRequest;
use Modules\DesignFramework\Presentation\Http\Requests\UpdateFrameworkVersionRequest;
use Modules\DesignFramework\Presentation\Http\Resources\FrameworkVersionResource;

/**
 * The editions of one methodology.
 *
 * Nested under the framework because a version number means nothing without saying v1 of what, and
 * resolved through it, so a version from another framework 404s at resolution rather than being
 * caught later by a policy.
 */
class FrameworkVersionController extends Controller
{
    /**
     * List the framework's editions, oldest first.
     */
    public function index(
        Request $request,
        Framework $framework,
        GetFrameworkVersions $getVersions,
    ): AnonymousResourceCollection {
        Gate::authorize('view', $framework);

        return FrameworkVersionResource::collection(
            $getVersions->handle($framework, Gate::allows('createVersion', $framework)),
        );
    }

    /**
     * Open a new edition.
     *
     * Allowed on a published framework, which is the mechanism by which a methodology evolves: the
     * only way to change a published version is to create the next one.
     */
    public function store(
        CreateFrameworkVersionRequest $request,
        Framework $framework,
        CreateFrameworkVersion $createVersion,
    ): JsonResponse {
        $version = $createVersion->handle($request->user(), $framework, $request->toData());

        return FrameworkVersionResource::make($version->loadCount(['phases', 'adoptions']))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show one edition.
     */
    public function show(Request $request, Framework $framework, FrameworkVersion $version): FrameworkVersionResource
    {
        Gate::authorize('viewVersion', $version);

        return FrameworkVersionResource::make($version->loadCount(['phases', 'adoptions']));
    }

    /**
     * Change a draft edition's name or description.
     */
    public function update(
        UpdateFrameworkVersionRequest $request,
        Framework $framework,
        FrameworkVersion $version,
        UpdateFrameworkVersion $updateVersion,
    ): FrameworkVersionResource {
        $updateVersion->handle($request->user(), $version, $request->toData());

        return FrameworkVersionResource::make($version->loadCount(['phases', 'adoptions']));
    }
}
