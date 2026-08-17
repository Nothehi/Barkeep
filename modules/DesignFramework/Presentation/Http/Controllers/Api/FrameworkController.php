<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\DesignFramework\Application\Commands\CreateFramework;
use Modules\DesignFramework\Application\Commands\UpdateFramework;
use Modules\DesignFramework\Application\Queries\GetFrameworks;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Presentation\Http\Requests\CreateFrameworkRequest;
use Modules\DesignFramework\Presentation\Http\Requests\FrameworkFilterRequest;
use Modules\DesignFramework\Presentation\Http\Requests\UpdateFrameworkRequest;
use Modules\DesignFramework\Presentation\Http\Resources\FrameworkResource;

/**
 * The platform's design methodologies.
 *
 * The one collection in the application that is not nested under a workspace, because a framework is
 * not a studio's document — it is something Barkeep publishes and studios adopt.
 *
 * What takes the place of tenancy scoping is the draft filter. Drafts are visible only to framework
 * administrators, and the question of whether the caller is one is asked of the policy rather than
 * worked out here: `create` is exactly the "administers frameworks" ability, so it is the honest test
 * for whether unfinished methodology should be disclosed.
 */
class FrameworkController extends Controller
{
    /**
     * List the frameworks the caller may see.
     */
    public function index(FrameworkFilterRequest $request, GetFrameworks $getFrameworks): AnonymousResourceCollection
    {
        return FrameworkResource::collection(
            $getFrameworks->handle($request->toFilters(), $this->maySeeDrafts()),
        );
    }

    /**
     * Start writing a new methodology.
     */
    public function store(CreateFrameworkRequest $request, CreateFramework $createFramework): JsonResponse
    {
        $framework = $createFramework->handle($request->user(), $request->toData());

        return FrameworkResource::make($framework->loadCount('versions'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show one framework.
     */
    public function show(Request $request, Framework $framework): FrameworkResource
    {
        Gate::authorize('view', $framework);

        return FrameworkResource::make(
            $framework->load('latestVersion')->loadCount('versions'),
        );
    }

    /**
     * Change a framework's name, address or description.
     */
    public function update(
        UpdateFrameworkRequest $request,
        Framework $framework,
        UpdateFramework $updateFramework,
    ): FrameworkResource {
        $updateFramework->handle($request->user(), $framework, $request->toData());

        return FrameworkResource::make(
            $framework->load('latestVersion')->loadCount('versions'),
        );
    }

    /**
     * Whether the caller administers frameworks, and may therefore see drafts.
     *
     * Asked of the policy rather than of the configuration list, so there is one definition of who
     * administers methodology and every screen and endpoint agrees with it.
     */
    private function maySeeDrafts(): bool
    {
        return Gate::allows('create', Framework::class);
    }
}
