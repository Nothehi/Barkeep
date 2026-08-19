<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\CreateBalanceProfile;
use Modules\GameEconomy\Application\Commands\UpdateBalanceProfile;
use Modules\GameEconomy\Application\Queries\GetBalanceProfiles;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Presentation\Http\Requests\BalanceProfileFilterRequest;
use Modules\GameEconomy\Presentation\Http\Requests\CreateBalanceProfileRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateBalanceProfileRequest;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceProfileResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * A design state's balance configurations.
 *
 * Nested under the version the same way versions are nested under the game and
 * games under the workspace, and resolved through the same chained bindings — so
 * a profile id from another project cannot be reached from here.
 *
 * The whole chain is walked by the router before any handler runs, which is why
 * `{profile}` can be an opaque uuid in a URL without being a capability.
 */
class BalanceProfileController extends Controller
{
    /**
     * List the design state's configurations, newest first.
     */
    public function index(
        BalanceProfileFilterRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        GetBalanceProfiles $getProfiles,
    ): AnonymousResourceCollection {
        return BalanceProfileResource::collection($getProfiles->handle($version));
    }

    /**
     * Start configuring the economy of this design state.
     */
    public function store(
        CreateBalanceProfileRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        CreateBalanceProfile $createProfile,
    ): JsonResponse {
        $profile = $createProfile->handle($request->user(), $game, $version, $request->toData());

        return BalanceProfileResource::make($profile)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show one configuration in full.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
    ): BalanceProfileResource {
        Gate::authorize('view', $profile);

        return BalanceProfileResource::make(
            $profile->load(['version', 'creator'])->loadCount([
                'resources', 'flows', 'actions', 'variables', 'scenarios', 'snapshots',
            ]),
        );
    }

    /**
     * Change a configuration's own name and description.
     */
    public function update(
        UpdateBalanceProfileRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        UpdateBalanceProfile $updateProfile,
    ): BalanceProfileResource {
        $updateProfile->handle($request->user(), $profile, $request->toData());

        return BalanceProfileResource::make($profile->load(['version', 'creator']));
    }
}
