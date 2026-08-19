<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\CreatePrototypeVersion;
use Modules\PrototypeIteration\Application\Queries\GetPrototypeVersions;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreatePrototypeVersionRequest;
use Modules\PrototypeIteration\Presentation\Http\Resources\PrototypeVersionResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The states of a prototype.
 *
 * Addressed by number rather than by id — `/prototypes/{prototype}/versions/3` — which is both how
 * a designer says it and what makes the scoping structural: a number is only meaningful inside one
 * prototype, so there is no version address that does not carry its parent.
 *
 * There is no update or delete route, and that absence is the immutability rule as a routing
 * property. A version that has been built upon is part of the design record; the way forward is to
 * cut the next one, which is what the store route is for and why it asks for nothing.
 */
class PrototypeVersionController extends Controller
{
    /**
     * List the prototype's states, newest first.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        Prototype $prototype,
        GetPrototypeVersions $getVersions,
    ): AnonymousResourceCollection {
        Gate::authorize('viewVersions', $prototype);

        return PrototypeVersionResource::collection($getVersions->handle($prototype));
    }

    /**
     * Cut the next state of the prototype.
     */
    public function store(
        CreatePrototypeVersionRequest $request,
        Workspace $workspace,
        Game $game,
        Prototype $prototype,
        CreatePrototypeVersion $createVersion,
    ): JsonResponse {
        $version = $createVersion->handle($request->user(), $prototype, $request->toData());

        return PrototypeVersionResource::make(
            $version->loadCount(['artifacts', 'iterations']),
        )->response()->setStatusCode(201);
    }

    /**
     * Show one state of the prototype.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        Prototype $prototype,
        PrototypeVersion $prototypeVersion,
    ): PrototypeVersionResource {
        Gate::authorize('viewVersions', $prototype);

        return PrototypeVersionResource::make(
            $prototypeVersion->load('creator')->loadCount(['artifacts', 'iterations']),
        );
    }
}
