<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\DesignFramework\Application\Commands\PublishFramework;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Presentation\Http\Resources\FrameworkResource;

/**
 * Making a framework visible to every designer on the platform.
 *
 * A POST to a named sub-resource rather than a PATCH of the status field: publishing is an action
 * with consequences — it freezes the framework's own record — and a route that looked like a field
 * would invite a client to set one.
 */
class FrameworkPublicationController extends Controller
{
    /**
     * Publish the framework.
     */
    public function store(Request $request, Framework $framework, PublishFramework $publishFramework): FrameworkResource
    {
        Gate::authorize('publish', $framework);

        $publishFramework->handle($request->user(), $framework);

        return FrameworkResource::make($framework->load('latestVersion')->loadCount('versions'));
    }
}
