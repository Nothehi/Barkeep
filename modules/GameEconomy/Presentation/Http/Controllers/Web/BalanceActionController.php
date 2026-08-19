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
use Modules\GameEconomy\Application\Commands\CreateEconomyAction;
use Modules\GameEconomy\Application\Commands\DeleteEconomyAction;
use Modules\GameEconomy\Application\Commands\UpdateEconomyAction;
use Modules\GameEconomy\Application\Queries\GetResources;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\Models\EconomyAction;
use Modules\GameEconomy\Infrastructure\Calculations\BalanceCalculator;
use Modules\GameEconomy\Presentation\Http\Controllers\Web\Concerns\ProvidesBalanceVocabulary;
use Modules\GameEconomy\Presentation\Http\Requests\ConfigurationChangeRequest;
use Modules\GameEconomy\Presentation\Http\Requests\CreateEconomyActionRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateEconomyActionRequest;
use Modules\GameEconomy\Presentation\Http\Resources\ActionProfitabilityResource;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceProfileResource;
use Modules\GameEconomy\Presentation\Http\Resources\ConversionRatioResource;
use Modules\GameEconomy\Presentation\Http\Resources\EconomyActionResource;
use Modules\GameEconomy\Presentation\Http\Resources\ResourceTypeResource;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * The action screens.
 */
class BalanceActionController extends Controller
{
    use ProvidesBalanceVocabulary;

    /**
     * Declare something that moves the economy.
     */
    public function store(
        CreateEconomyActionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        CreateEconomyAction $createAction,
    ): RedirectResponse {
        $action = $createAction->handle($request->user(), $profile, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Action added.')]);

        return to_route('balance.actions.show', [$workspace, $game, $version, $profile, $action]);
    }

    /**
     * Show one action: what it takes, what it gives, and what else it does.
     *
     * The resources list travels with it because every editor on this page is a
     * resource picker, and the conversions because "2 wood → 1 gold" is the
     * sentence this screen exists to make immediately readable.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        GetResources $getResources,
        BalanceCalculator $calculator,
    ): Response {
        Gate::authorize('view', $profile);

        $economyAction->load(['costs.resource', 'rewards.resource', 'effects']);

        return Inertia::render('balance/action', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),
            'version' => GameVersionResource::make($version),
            'profile' => BalanceProfileResource::make($profile->load(['version', 'creator'])),
            'action' => EconomyActionResource::make($economyAction),
            'resources' => ResourceTypeResource::collection($getResources->handle($profile)),
            'profitability' => ActionProfitabilityResource::make($calculator->profitabilityOf($economyAction)),
            'conversions' => ConversionRatioResource::collection($calculator->conversionsOf($economyAction)),
            'options' => $this->balanceVocabulary(),
        ]);
    }

    /**
     * Rename an action or change what it is for.
     */
    public function update(
        UpdateEconomyActionRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        UpdateEconomyAction $updateAction,
    ): RedirectResponse {
        $updateAction->handle($request->user(), $economyAction, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Action updated.')]);

        return back();
    }

    /**
     * Remove an action, and everything it did with it.
     */
    public function destroy(
        ConfigurationChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        EconomyAction $economyAction,
        DeleteEconomyAction $deleteAction,
    ): RedirectResponse {
        $deleteAction->handle($request->user(), $economyAction);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Action removed.')]);

        return to_route('balance.show', [$workspace, $game, $version, $profile]);
    }
}
