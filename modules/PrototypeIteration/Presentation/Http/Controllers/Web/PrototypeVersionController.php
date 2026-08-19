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
use Modules\PrototypeIteration\Application\Commands\CreatePrototypeVersion;
use Modules\PrototypeIteration\Application\Queries\GetPrototypeArtifacts;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\PrototypeIteration\Presentation\Http\Controllers\Web\Concerns\ProvidesDesignVocabulary;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreatePrototypeVersionRequest;
use Modules\PrototypeIteration\Presentation\Http\Resources\PrototypeArtifactResource;
use Modules\PrototypeIteration\Presentation\Http\Resources\PrototypeResource;
use Modules\PrototypeIteration\Presentation\Http\Resources\PrototypeVersionResource;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * The screens for one state of a prototype.
 *
 * There is no edit screen and no edit route, which is the immutability rule showing up in the
 * interface rather than only in an error message. What the version screen offers instead is
 * "cut the next version", which is the move a designer actually wants when they find they cannot
 * change v3.
 */
class PrototypeVersionController extends Controller
{
    use ProvidesDesignVocabulary;

    /**
     * Cut the next state of a prototype.
     *
     * Lands on the new version rather than staying put, because cutting one is almost always
     * followed by uploading its files.
     */
    public function store(
        CreatePrototypeVersionRequest $request,
        Workspace $workspace,
        Game $game,
        Prototype $prototype,
        CreatePrototypeVersion $createVersion,
    ): RedirectResponse {
        $version = $createVersion->handle($request->user(), $prototype, $request->toData());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Version :label created.', ['label' => $version->label()]),
        ]);

        return to_route('prototypes.versions.show', [$workspace, $game, $prototype, $version]);
    }

    /**
     * Show one state of a prototype and the files that make it buildable.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        Prototype $prototype,
        PrototypeVersion $prototypeVersion,
        GetPrototypeArtifacts $getArtifacts,
    ): Response {
        Gate::authorize('viewVersions', $prototype);

        return Inertia::render('prototypes/version', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),
            'prototype' => PrototypeResource::make(
                $prototype->load(['version', 'creator'])->loadCount('versions'),
            ),
            'version' => PrototypeVersionResource::make(
                $prototypeVersion->load('creator')->loadCount(['artifacts', 'iterations']),
            ),
            'artifacts' => PrototypeArtifactResource::collection($getArtifacts->handle($prototypeVersion)),
            'options' => $this->prototypeVocabulary(),
        ]);
    }
}
