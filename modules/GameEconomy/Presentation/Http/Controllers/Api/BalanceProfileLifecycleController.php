<?php

namespace Modules\GameEconomy\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameEconomy\Application\Commands\ActivateBalanceProfile;
use Modules\GameEconomy\Application\Commands\ArchiveBalanceProfile;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Presentation\Http\Requests\BalanceProfileLifecycleRequest;
use Modules\GameEconomy\Presentation\Http\Resources\BalanceProfileResource;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Putting a configuration into play, and putting it away.
 *
 * Separate named actions rather than a PATCH of the status field, because they
 * are moves with rules: activating retires whichever profile was in play, and
 * archiving cannot be undone. Neither is an editable attribute.
 */
class BalanceProfileLifecycleController extends Controller
{
    /**
     * Make this the configuration in play.
     */
    public function activate(
        BalanceProfileLifecycleRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        BalanceProfile $profile,
        ActivateBalanceProfile $activate,
    ): BalanceProfileResource {
        $activate->handle($request->user(), $profile);

        return BalanceProfileResource::make($profile->load(['version', 'creator']));
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
    ): BalanceProfileResource {
        $archive->handle($request->user(), $profile);

        return BalanceProfileResource::make($profile->load(['version', 'creator']));
    }
}
