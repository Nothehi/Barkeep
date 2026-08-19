<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\CreatePrototype;
use Modules\PrototypeIteration\Application\Commands\UpdatePrototype;
use Modules\PrototypeIteration\Application\Queries\GetPrototypes;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreatePrototypeRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\PrototypeFilterRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\UpdatePrototypeRequest;
use Modules\PrototypeIteration\Presentation\Http\Resources\PrototypeCardResource;
use Modules\PrototypeIteration\Presentation\Http\Resources\PrototypeResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * A game's prototypes.
 *
 * Nested under the game the same way games are nested under the workspace, and resolved through
 * the same chained bindings, so a prototype id from another project cannot be reached from here.
 *
 * The list returns cards and the detail route returns everything. A prototypes screen renders
 * many rows and needs none of the per-prototype permission and transition answers the full
 * resource computes.
 */
class PrototypeController extends Controller
{
    /**
     * List the game's prototypes, newest first.
     */
    public function index(
        PrototypeFilterRequest $request,
        Workspace $workspace,
        Game $game,
        GetPrototypes $getPrototypes,
    ): AnonymousResourceCollection {
        return PrototypeCardResource::collection(
            $getPrototypes->handle($game, $request->toFilters()),
        );
    }

    /**
     * Start a prototype from a version of the game's design.
     */
    public function store(
        CreatePrototypeRequest $request,
        Workspace $workspace,
        Game $game,
        CreatePrototype $createPrototype,
    ): JsonResponse {
        $prototype = $createPrototype->handle($request->user(), $game, $request->toData());

        return PrototypeResource::make($prototype->loadCount('versions'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show one prototype in full.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        Prototype $prototype,
    ): PrototypeResource {
        Gate::authorize('view', $prototype);

        return PrototypeResource::make(
            $prototype->load(['version', 'creator'])->loadCount('versions'),
        );
    }

    /**
     * Change a prototype's own details.
     */
    public function update(
        UpdatePrototypeRequest $request,
        Workspace $workspace,
        Game $game,
        Prototype $prototype,
        UpdatePrototype $updatePrototype,
    ): PrototypeResource {
        $updatePrototype->handle($request->user(), $prototype, $request->toData());

        return PrototypeResource::make(
            $prototype->load(['version', 'creator'])->loadCount('versions'),
        );
    }
}
