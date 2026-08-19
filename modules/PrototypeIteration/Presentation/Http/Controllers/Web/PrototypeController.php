<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Presentation\Http\Resources\GameResource;
use Modules\GameDesign\Presentation\Http\Resources\GameVersionResource;
use Modules\PrototypeIteration\Application\Commands\CreatePrototype;
use Modules\PrototypeIteration\Application\Commands\UpdatePrototype;
use Modules\PrototypeIteration\Application\Queries\GetPrototypes;
use Modules\PrototypeIteration\Application\Queries\GetPrototypeVersions;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Infrastructure\Authorization\PrototypePermissions;
use Modules\PrototypeIteration\Infrastructure\GameDesign\GameCatalogue;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Web\Concerns\ProvidesDesignVocabulary;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreatePrototypeRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\PrototypeFilterRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\UpdatePrototypeRequest;
use Modules\PrototypeIteration\Presentation\Http\Resources\PrototypeCardResource;
use Modules\PrototypeIteration\Presentation\Http\Resources\PrototypeResource;
use Modules\PrototypeIteration\Presentation\Http\Resources\PrototypeVersionResource;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * The prototype screens.
 *
 * These render pages and hand off to the same application commands, form requests and queries the
 * JSON API uses, so there is one implementation of every rule and two ways to reach it.
 */
class PrototypeController extends Controller
{
    use ProvidesDesignVocabulary;

    /**
     * Show the game's prototypes.
     *
     * The filters are echoed back so the screen can render what it is currently showing without
     * keeping its own copy of the query string, and the option lists come from the enums rather
     * than being restated in TypeScript.
     */
    public function index(
        PrototypeFilterRequest $request,
        Workspace $workspace,
        Game $game,
        GetPrototypes $getPrototypes,
        GameCatalogue $catalogue,
    ): Response {
        $filters = $request->toFilters();

        return Inertia::render('prototypes/index', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),
            'prototypes' => PrototypeCardResource::collection($getPrototypes->handle($game, $filters)),
            'versions' => GameVersionResource::collection($catalogue->versionsOf($game)),
            'filters' => [
                'search' => $filters->search,
                'status' => $filters->status?->value,
                'type' => $filters->type?->value,
            ],
            'options' => $this->prototypeVocabulary(),
            'can' => [
                'create' => app(PrototypePermissions::class)->canCreateFor($request->user(), $game),
            ],
        ]);
    }

    /**
     * Start a prototype and go straight into it.
     */
    public function store(
        CreatePrototypeRequest $request,
        Workspace $workspace,
        Game $game,
        CreatePrototype $createPrototype,
    ): RedirectResponse {
        $prototype = $createPrototype->handle($request->user(), $game, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Prototype created.')]);

        return to_route('prototypes.show', [$workspace, $game, $prototype]);
    }

    /**
     * Show a prototype: what it is, and every state it has been in.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        Prototype $prototype,
        GetPrototypeVersions $getVersions,
    ): Response {
        Gate::authorize('view', $prototype);

        return Inertia::render('prototypes/show', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),
            'prototype' => PrototypeResource::make(
                $prototype->load(['version', 'creator'])->loadCount('versions'),
            ),
            'versions' => PrototypeVersionResource::collection($getVersions->handle($prototype)),
            'options' => $this->prototypeVocabulary(),
        ]);
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
    ): RedirectResponse {
        $updatePrototype->handle($request->user(), $prototype, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Prototype updated.')]);

        return back();
    }
}
