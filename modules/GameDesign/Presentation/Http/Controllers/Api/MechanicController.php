<?php

namespace Modules\GameDesign\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Application\Commands\CreateMechanic;
use Modules\GameDesign\Application\Commands\UpdateMechanic;
use Modules\GameDesign\Application\Queries\GetMechanics;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\GameDesign\Infrastructure\Authorization\MechanicPermissions;
use Modules\GameDesign\Presentation\Http\Requests\CreateMechanicRequest;
use Modules\GameDesign\Presentation\Http\Requests\UpdateMechanicRequest;
use Modules\GameDesign\Presentation\Http\Resources\MechanicResource;

/**
 * The design vocabulary over JSON.
 *
 * The one collection in this module that takes no workspace. That is not an
 * oversight to be tightened later: a mechanic carries nothing about anybody,
 * and scoping the vocabulary to a studio would defeat the only reason it is
 * shared.
 */
class MechanicController extends Controller
{
    /**
     * List the vocabulary.
     */
    public function index(Request $request, GetMechanics $getMechanics): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Mechanic::class);

        return MechanicResource::collection($getMechanics->handle(
            includeArchived: app(MechanicPermissions::class)->canCreate($request->user()),
        ));
    }

    /**
     * Show one term.
     */
    public function show(Request $request, Mechanic $mechanic): MechanicResource
    {
        Gate::authorize('view', $mechanic);

        return MechanicResource::make($mechanic);
    }

    /**
     * Add a term to the vocabulary.
     */
    public function store(CreateMechanicRequest $request, CreateMechanic $createMechanic): JsonResponse
    {
        $mechanic = $createMechanic->handle($request->user(), $request->toData());

        return MechanicResource::make($mechanic)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Change what a term is called or means.
     */
    public function update(
        UpdateMechanicRequest $request,
        Mechanic $mechanic,
        UpdateMechanic $updateMechanic,
    ): MechanicResource {
        return MechanicResource::make(
            $updateMechanic->handle($request->user(), $mechanic, $request->toData()),
        );
    }
}
