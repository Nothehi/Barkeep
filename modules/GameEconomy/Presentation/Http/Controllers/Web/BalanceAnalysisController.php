<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\AnalyseBalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Analysing a configuration on a designer's explicit say-so.
 *
 * The dashboard already shows the findings — it reads them through the silent
 * query — so this endpoint exists for one reason: pressing "Analyse" is a fact
 * about how a studio works, and dispatching an event for it is how anything
 * built later gets to know that a team checks their economy before a playtest
 * rather than after one goes wrong.
 *
 * Nothing is written, and nothing is different in the response. It reloads the
 * same page with the same numbers.
 */
class BalanceAnalysisController extends Controller
{
    /**
     * Analyse the configuration, and record that somebody did.
     */
    public function store(
        Request $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        AnalyseBalanceProfile $analyse,
    ): RedirectResponse {
        Gate::authorize('view', $profile);

        $analysis = $analyse->handle($profile);

        Inertia::flash('toast', [
            'type' => $analysis->summary->hasErrors() ? 'error' : 'success',
            'message' => __('Analysis complete.'),
        ]);

        return back();
    }
}
