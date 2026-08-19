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
use Modules\GameEconomy\Application\Commands\CreateBalanceProfile;
use Modules\GameEconomy\Application\Commands\UpdateBalanceProfile;
use Modules\GameEconomy\Application\Queries\GetBalanceAnalysis;
use Modules\GameEconomy\Application\Queries\GetBalanceAssumptions;
use Modules\GameEconomy\Application\Queries\GetBalanceObservations;
use Modules\GameEconomy\Application\Queries\GetBalanceProfiles;
use Modules\GameEconomy\Application\Queries\GetBalanceScenarios;
use Modules\GameEconomy\Application\Queries\GetBalanceSnapshots;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Infrastructure\Authorization\BalanceProfilePermissions;
use Modules\GameEconomy\Presentation\Http\Controllers\Web\Concerns\ProvidesBalanceVocabulary;
use Modules\GameEconomy\Presentation\Http\Requests\CreateBalanceProfileRequest;
use Modules\GameEconomy\Presentation\Http\Requests\UpdateBalanceProfileRequest;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceAnalysisResource;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceAssumptionResource;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceObservationResource;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceProfileResource;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceScenarioResource;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceSnapshotResource;
use Modules\Workspace\Domain\Models\Workspace;
use Modules\Workspace\Presentation\Http\Resources\WorkspaceResource;

/**
 * The balance screens.
 *
 * These render pages and hand off to the same application commands, form
 * requests and queries the JSON API uses, so there is one implementation of
 * every rule and two ways to reach it.
 *
 * The dashboard sends the whole configuration in one response rather than
 * letting the page fetch each section. That is a deliberate departure from how
 * the iteration screens work, and the reason is that these sections are not
 * independent: the analysis is *about* the resources, the actions and the
 * variables, and a screen that loaded them in four requests would spend part of
 * its life showing findings about a configuration it had not finished
 * receiving.
 */
class BalanceController extends Controller
{
    use ProvidesBalanceVocabulary;

    /**
     * Show the design state's balance configurations.
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        GetBalanceProfiles $getProfiles,
    ): Response {
        Gate::authorize('viewAny', [BalanceProfile::class, $version]);

        return Inertia::render('balance/index', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),
            'version' => GameVersionResource::make($version),
            'profiles' => BalanceProfileResource::collection($getProfiles->handle($version)),
            'options' => $this->balanceVocabulary(),
            'can' => [
                'create' => app(BalanceProfilePermissions::class)->canCreateFor($request->user(), $version),
            ],
        ]);
    }

    /**
     * Start configuring the economy and go straight into it.
     */
    public function store(
        CreateBalanceProfileRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        CreateBalanceProfile $createProfile,
    ): RedirectResponse {
        $profile = $createProfile->handle($request->user(), $game, $version, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Balance profile created.')]);

        return to_route('balance.show', [$workspace, $game, $version, $profile]);
    }

    /**
     * The balance dashboard for one configuration.
     *
     * The analysis is read through the silent query rather than the command, so
     * that opening the screen does not record that somebody analysed the economy
     * — pressing "Analyse" does, and the distinction is the whole reason both
     * exist.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        GetBalanceAnalysis $getAnalysis,
        GetBalanceScenarios $getScenarios,
        GetBalanceAssumptions $getAssumptions,
        GetBalanceObservations $getObservations,
        GetBalanceSnapshots $getSnapshots,
    ): Response {
        Gate::authorize('view', $profile);

        $profile->load(['version', 'creator']);

        return Inertia::render('balance/show', [
            'workspace' => WorkspaceResource::make($workspace),
            'game' => GameResource::make($game->loadCount('versions')),
            'version' => GameVersionResource::make($version),
            'profile' => BalanceProfileResource::make($profile),
            'analysis' => BalanceAnalysisResource::make($getAnalysis->handle($profile)),
            'scenarios' => BalanceScenarioResource::collection(
                $getScenarios->handle($profile)->each->load('overrides.variable'),
            ),
            'assumptions' => BalanceAssumptionResource::collection($getAssumptions->handle($profile)),
            'observations' => BalanceObservationResource::collection($getObservations->handle($profile)),
            'snapshots' => BalanceSnapshotResource::collection($getSnapshots->handle($profile)),
            'options' => $this->balanceVocabulary(),
        ]);
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
    ): RedirectResponse {
        $updateProfile->handle($request->user(), $profile, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Balance profile updated.')]);

        return back();
    }
}
