<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Queries\CompareBalanceSnapshots;
use Modules\GameEconomy\Application\Queries\GetBalanceSnapshot;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceSnapshot;
use Modules\GameEconomy\Presentation\Http\Requests\CompareBalanceSnapshotsRequest;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceComparisonResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The difference between two frozen configurations.
 *
 * The two snapshots arrive as query parameters rather than as route segments,
 * because a comparison is not a resource — it is a question about two of them,
 * and a nested path would imply one snapshot owned the other.
 *
 * They are still resolved *through* the profile, so a snapshot id from another
 * configuration fails to resolve exactly as it would in a URL. That is the whole
 * reason this controller looks them up itself rather than binding them: a query
 * parameter has no binder, and without this the module's one ownership rule
 * would have one exception.
 */
class BalanceComparisonController extends Controller
{
    /**
     * Compare two of the configuration's frozen states.
     */
    public function show(
        CompareBalanceSnapshotsRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        GetBalanceSnapshot $getSnapshot,
        CompareBalanceSnapshots $compare,
    ): BalanceComparisonResource {
        $from = $this->resolve($getSnapshot, $profile, (string) $request->validated('from'));
        $to = $this->resolve($getSnapshot, $profile, (string) $request->validated('to'));

        return BalanceComparisonResource::make($compare->handle($from, $to));
    }

    /**
     * Resolve one snapshot through the profile, or 404.
     */
    private function resolve(GetBalanceSnapshot $getSnapshot, BalanceProfile $profile, string $id): BalanceSnapshot
    {
        return $getSnapshot->handle($profile, $id)
            ?? throw (new ModelNotFoundException)->setModel(BalanceSnapshot::class, [$id]);
    }
}
