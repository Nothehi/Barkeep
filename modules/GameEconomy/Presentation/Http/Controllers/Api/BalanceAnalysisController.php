<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\AnalyseBalanceProfile;
use Modules\GameEconomy\Application\Queries\GetBalanceAnalysis;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceAnalysisResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * A reading of a configuration.
 *
 * Two verbs on one address, and the difference is whether anybody is told that
 * somebody looked.
 *
 * `GET` is what screens call. It computes the findings and says nothing, because
 * a dashboard recomputing its summary on every page load would otherwise fill a
 * studio's history with the fact that a page was refreshed.
 *
 * `POST` is the explicit "analyse this" action. It computes exactly the same
 * findings — nothing is written either way, section 31 — and dispatches an
 * event, because whether a team analyses before every playtest or only after
 * something goes wrong is a fact about their process that cannot be
 * reconstructed afterwards.
 *
 * Neither persists the result. An analysis is a reading of the configuration as
 * it stands, and storing one would create a second question the module would
 * then have to keep answering.
 */
class BalanceAnalysisController extends Controller
{
    /**
     * Read the configuration's findings.
     */
    public function show(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        GetBalanceAnalysis $getAnalysis,
    ): BalanceAnalysisResource {
        Gate::authorize('view', $profile);

        return BalanceAnalysisResource::make($getAnalysis->handle($profile));
    }

    /**
     * Analyse the configuration, and say so.
     */
    public function store(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        AnalyseBalanceProfile $analyse,
    ): BalanceAnalysisResource {
        Gate::authorize('view', $profile);

        return BalanceAnalysisResource::make($analyse->handle($profile));
    }
}
