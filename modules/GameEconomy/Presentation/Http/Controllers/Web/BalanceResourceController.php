<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameDesign\Presentation\Http\Resources\GameResource;
use Modules\GameDesign\Presentation\Http\Resources\GameVersionResource;
use Modules\GameEconomy\Application\Commands\CreateResourceType;
use Modules\GameEconomy\Application\Commands\DeleteResourceType;
use Modules\GameEconomy\Application\Commands\UpdateResourceType;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\ResourceType;
use Modules\GameEconomy\Infrastructure\Calculations\BalanceCalculator;
use Modules\GameEconomy\Infrastructure\Persistence\Repositories\EconomyRepository;
use Modules\GameEconomy\Presentation\Http\Controllers\Web\Concerns\ProvidesBalanceVocabulary;
use Modules\GameEconomy\Presentation\Http\Requests\ConfigurationChangeRequest;
use Modules\GameEconomy\Presentation\Http\Requests\CreateResourceRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateResourceRequest;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceProfileResource;
use Modules\GameEconomy\Presentation\Http\Resources\EconomyActionResource;
use Modules\GameEconomy\Presentation\Http\Resources\ResourceFlowResource;
use Modules\GameEconomy\Presentation\Http\Resources\ResourceNetFlowResource;
use Modules\GameEconomy\Presentation\Http\Resources\ResourceTypeResource;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * The resource screens.
 */
class BalanceResourceController extends Controller
{
    use ProvidesBalanceVocabulary;

    /**
     * Declare something players hold and spend.
     */
    public function store(
        CreateResourceRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        CreateResourceType $createResource,
    ): RedirectResponse {
        $createResource->handle($request->user(), $profile, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Resource added.')]);

        return back();
    }

    /**
     * Show one resource and what moves it.
     *
     * The net flow is computed here rather than on the client, so the figures on
     * this page and the figures in the analysis come from the same arithmetic —
     * and so that nothing has to parse a decimal string back into a float to
     * subtract two of them.
     *
     * The actions travel with their costs and rewards because the flow diagram
     * draws them alongside the declared flows: an action spending five wood
     * removes wood whether or not anybody also wrote a consumption flow for it,
     * and a picture showing only the flows would tell a designer their most
     * expensive action does not exist.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        ResourceType $resourceType,
        EconomyRepository $economy,
        BalanceCalculator $calculator,
    ): Response {
        Gate::authorize('view', $profile);

        return Inertia::render('balance/resource', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),
            'version' => GameVersionResource::make($version),
            'profile' => BalanceProfileResource::make($profile->load(['version', 'creator'])),
            'resource' => ResourceTypeResource::make($resourceType->loadCount(['flows', 'costs', 'rewards'])),
            'flows' => ResourceFlowResource::collection($economy->flowsOfResource($resourceType)),
            'net_flow' => ResourceNetFlowResource::make($calculator->netFlowFor($profile, $resourceType)),
            'actions' => EconomyActionResource::collection($economy->actionsWithEconomicsOf($profile)),
            'options' => $this->balanceVocabulary(),
        ]);
    }

    /**
     * Retune a resource.
     */
    public function update(
        UpdateResourceRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        ResourceType $resourceType,
        UpdateResourceType $updateResource,
    ): RedirectResponse {
        $updateResource->handle($request->user(), $resourceType, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Resource updated.')]);

        return back();
    }

    /**
     * Remove a resource nothing depends on.
     */
    public function destroy(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        ResourceType $resourceType,
        DeleteResourceType $deleteResource,
    ): RedirectResponse {
        $deleteResource->handle($request->user(), $resourceType);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Resource removed.')]);

        return to_route('balance.show', [$workspace, $game, $version, $profile]);
    }
}
