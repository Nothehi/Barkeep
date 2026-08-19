<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\CreatePrototypeArtifact;
use Modules\PrototypeIteration\Application\Commands\DeletePrototypeArtifact;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeArtifact;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\PrototypeIteration\Infrastructure\Storage\ArtifactStorage;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreateArtifactRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\DeleteArtifactRequest;
use Modules\Workspace\Domain\Models\Workspace;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Uploading, removing and downloading a prototype's files, from the screen.
 *
 * The download route is the reason this controller exists at all rather than the API one serving
 * both. An artifact is private — a studio's unreleased card art is exactly the thing that must not
 * leak by being guessable — so there is no public URL and no signed one either: the file is
 * streamed through a route that authorizes first, on every request.
 */
class PrototypeArtifactController extends Controller
{
    /**
     * Attach a file to a state of a prototype.
     */
    public function store(
        CreateArtifactRequest $request,
        Workspace $workspace,
        Game $game,
        Prototype $prototype,
        PrototypeVersion $prototypeVersion,
        CreatePrototypeArtifact $createArtifact,
    ): RedirectResponse {
        $createArtifact->handle($request->user(), $prototypeVersion, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('File attached.')]);

        return back();
    }

    /**
     * Remove a file from a state of a prototype.
     */
    public function destroy(
        DeleteArtifactRequest $request,
        Workspace $workspace,
        Game $game,
        Prototype $prototype,
        PrototypeVersion $prototypeVersion,
        PrototypeArtifact $artifact,
        DeletePrototypeArtifact $deleteArtifact,
    ): RedirectResponse {
        $deleteArtifact->handle($request->user(), $artifact);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('File removed.')]);

        return back();
    }

    /**
     * Stream a file back under the name it was uploaded with.
     *
     * Authorized on `viewVersions` rather than on a write ability, because reading a prototype's
     * files is reading — somebody who can see an archived prototype's history can download the
     * print sheets that go with it.
     *
     * A streamed response rather than a redirect to a signed URL, deliberately. A signed URL is a
     * credential that outlives the check that issued it; streaming means the authorization and the
     * bytes travel together, and it behaves identically whatever disk is configured.
     */
    public function download(
        Request $request,
        Workspace $workspace,
        Game $game,
        Prototype $prototype,
        PrototypeVersion $prototypeVersion,
        PrototypeArtifact $artifact,
        ArtifactStorage $storage,
    ): StreamedResponse {
        Gate::authorize('viewVersions', $prototype);

        abort_unless($storage->exists($artifact), 404);

        return $storage->download($artifact);
    }
}
