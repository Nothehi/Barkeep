<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\CreatePrototypeArtifact;
use Modules\PrototypeIteration\Application\Commands\DeletePrototypeArtifact;
use Modules\PrototypeIteration\Application\Queries\GetPrototypeArtifacts;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeArtifact;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\PrototypeIteration\Presentation\Http\Requests\CreateArtifactRequest;
use Modules\PrototypeIteration\Presentation\Http\Requests\DeleteArtifactRequest;
use Modules\PrototypeIteration\Presentation\Http\Resources\PrototypeArtifactResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The files attached to a state of a prototype.
 *
 * Upload, list, delete — and nothing else, which is section 49's instruction. There is no folder
 * structure, no rename-and-move, no thumbnail generation and no revision history for a single
 * file: a second upload is a second artifact, and a corrected asset usually means the next
 * prototype version anyway.
 *
 * No raw filesystem handling appears here. The controller hands the upload to the command, the
 * command hands it to the storage adapter, and the adapter is the only thing in the module that
 * knows a disk exists.
 */
class PrototypeArtifactController extends Controller
{
    /**
     * List the files attached to this state, in upload order.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        Prototype $prototype,
        PrototypeVersion $prototypeVersion,
        GetPrototypeArtifacts $getArtifacts,
    ): AnonymousResourceCollection {
        Gate::authorize('viewVersions', $prototype);

        return PrototypeArtifactResource::collection($getArtifacts->handle($prototypeVersion));
    }

    /**
     * Attach a file to this state.
     */
    public function store(
        CreateArtifactRequest $request,
        Workspace $workspace,
        Game $game,
        Prototype $prototype,
        PrototypeVersion $prototypeVersion,
        CreatePrototypeArtifact $createArtifact,
    ): JsonResponse {
        $artifact = $createArtifact->handle($request->user(), $prototypeVersion, $request->toData());

        return PrototypeArtifactResource::make($artifact)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Remove a file from this state.
     *
     * Answers 204 rather than the deleted resource. The caller asked for the artifact to be gone,
     * and returning a representation of something that no longer exists would invite a client to
     * keep rendering it.
     */
    public function destroy(
        DeleteArtifactRequest $request,
        Workspace $workspace,
        Game $game,
        Prototype $prototype,
        PrototypeVersion $prototypeVersion,
        PrototypeArtifact $artifact,
        DeletePrototypeArtifact $deleteArtifact,
    ): JsonResponse {
        $deleteArtifact->handle($request->user(), $artifact);

        return response()->json(status: 204);
    }
}
