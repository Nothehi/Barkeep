<?php

namespace Modules\PrototypeIteration\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\GameDesign\Domain\Models\Game;
use Modules\PrototypeIteration\Application\Commands\ArchivePrototype;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Presentation\Http\Requests\ArchivePrototypeRequest;
use Modules\PrototypeIteration\Presentation\Http\Resources\PrototypeResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Putting a prototype away for good.
 *
 * Its own endpoint rather than a status field on the update route, because archival cannot be
 * undone — and an irreversible move should not be one field value away from a reversible one.
 * GameDesign takes the same approach for archiving a game.
 */
class PrototypeArchiveController extends Controller
{
    public function store(
        ArchivePrototypeRequest $request,
        Workspace $workspace,
        Game $game,
        Prototype $prototype,
        ArchivePrototype $archivePrototype,
    ): PrototypeResource {
        $archivePrototype->handle($request->user(), $prototype);

        return PrototypeResource::make(
            $prototype->load(['version', 'creator'])->loadCount('versions'),
        );
    }
}
