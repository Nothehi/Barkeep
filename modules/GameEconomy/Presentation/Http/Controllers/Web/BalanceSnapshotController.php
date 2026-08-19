<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameDesign\Presentation\Http\Resources\GameResource;
use Modules\GameDesign\Presentation\Http\Resources\GameVersionResource;
use Modules\GameEconomy\Application\Commands\CreateBalanceSnapshot;
use Modules\GameEconomy\Application\Queries\CompareBalanceSnapshots;
use Modules\GameEconomy\Application\Queries\GetBalanceSnapshot;
use Modules\GameEconomy\Application\Queries\GetBalanceSnapshots;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceSnapshot;
use Modules\GameEconomy\Presentation\Http\Requests\CompareBalanceSnapshotsRequest;
use Modules\GameEconomy\Presentation\Http\Requests\CreateBalanceSnapshotRequest;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceComparisonResource;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceProfileResource;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceSnapshotResource;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * Freezing a configuration, and reading the difference between two.
 *
 * There is no route that edits or deletes a snapshot. A snapshot is what the
 * economy was, so rewriting one would change what every playtest run against it
 * was played under, and deleting one would remove the only record of it.
 */
class BalanceSnapshotController extends Controller
{
    /**
     * Freeze the configuration as it stands.
     */
    public function store(
        CreateBalanceSnapshotRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        CreateBalanceSnapshot $createSnapshot,
    ): RedirectResponse {
        $createSnapshot->handle($request->user(), $profile, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Snapshot taken.')]);

        return back();
    }

    /**
     * Compare two of the configuration's frozen states.
     *
     * The pair arrives as query parameters, so both are looked up *through* the
     * profile here — a query parameter has no route binder, and without this the
     * module's one ownership rule would have an exception.
     */
    public function compare(
        CompareBalanceSnapshotsRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        GetBalanceSnapshot $getSnapshot,
        GetBalanceSnapshots $getSnapshots,
        CompareBalanceSnapshots $compare,
    ): Response {
        $from = $this->resolve($getSnapshot, $profile, (string) $request->validated('from'));
        $to = $this->resolve($getSnapshot, $profile, (string) $request->validated('to'));

        return Inertia::render('balance/comparison', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),
            'version' => GameVersionResource::make($version),
            'profile' => BalanceProfileResource::make($profile->load(['version', 'creator'])),
            'snapshots' => BalanceSnapshotResource::collection($getSnapshots->handle($profile)),
            'comparison' => BalanceComparisonResource::make($compare->handle($from, $to)),
        ]);
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
