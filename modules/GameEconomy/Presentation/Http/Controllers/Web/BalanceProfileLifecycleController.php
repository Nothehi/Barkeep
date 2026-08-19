<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\ActivateBalanceProfile;
use Modules\GameEconomy\Application\Commands\ArchiveBalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Presentation\Http\Requests\BalanceProfileLifecycleRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Putting a configuration into play, and putting it away.
 */
class BalanceProfileLifecycleController extends Controller
{
    /**
     * Make this the configuration in play.
     *
     * The toast says what happened to the configuration this one displaced,
     * because activating retires it — and a designer who did not realise that is
     * about to wonder where their other profile went.
     */
    public function activate(
        BalanceProfileLifecycleRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        ActivateBalanceProfile $activate,
    ): RedirectResponse {
        $activate->handle($request->user(), $profile);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('This balance profile is now in play. Any profile that was active has been archived.'),
        ]);

        return back();
    }

    /**
     * Put the configuration away for good.
     */
    public function archive(
        BalanceProfileLifecycleRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        ArchiveBalanceProfile $archive,
    ): RedirectResponse {
        $archive->handle($request->user(), $profile);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Balance profile archived.')]);

        return to_route('balance.index', [$workspace, $game, $version]);
    }
}
