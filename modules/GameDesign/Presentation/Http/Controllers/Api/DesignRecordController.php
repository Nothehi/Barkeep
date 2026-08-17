<?php

namespace Modules\GameDesign\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Application\Commands\UpdateDesignRecord;
use Modules\GameDesign\Application\Queries\GetDesignRecord;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Presentation\Http\Requests\UpdateDesignRecordRequest;
use Modules\GameDesign\Presentation\Http\Resources\DesignRecordResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * A game's design record over JSON.
 *
 * A singleton sub-resource rather than a collection: a game has one design, and
 * `GET` answers 404 when nothing has been decided. That is an honest answer to
 * "show me this game's design" and is the same shape a game's framework adoption
 * takes — while the settings screen, which has somewhere to put an empty form,
 * is sent null instead.
 *
 * There is no `POST`. The record is created by the first `PATCH`, because from
 * the caller's point of view there is nothing to create — the design exists as
 * soon as anything about it is known, and making them create a container first
 * would be the storage shape leaking into the API.
 */
class DesignRecordController extends Controller
{
    /**
     * Show what has been decided.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GetDesignRecord $getDesignRecord,
    ): DesignRecordResource {
        Gate::authorize('view', $game);

        $record = $getDesignRecord->handle($game);

        abort_if($record === null, 404, __('Nothing has been decided about this game\'s design yet.'));

        return DesignRecordResource::make($record);
    }

    /**
     * Record what has been decided.
     */
    public function update(
        UpdateDesignRecordRequest $request,
        Workspace $workspace,
        Game $game,
        UpdateDesignRecord $updateDesignRecord,
    ): DesignRecordResource {
        return DesignRecordResource::make(
            $updateDesignRecord->handle($request->user(), $game, $request->toData()),
        );
    }
}
