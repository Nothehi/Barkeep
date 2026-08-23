<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\ActivateRuleSet;
use Modules\GameRules\Application\Commands\ArchiveRuleSet;
use Modules\GameRules\Application\Commands\CloneRuleSet;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CloneRuleSetRequest;
use Modules\GameRules\Presentation\Http\Requests\RuleSetLifecycleRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Moving a rule set through its life.
 *
 * Three POSTs to named actions rather than a PATCH of a status field, because all
 * three are actions with rules — activating retires whichever set was in play and
 * is refused while errors stand, archiving cannot be undone, and cloning creates a
 * whole second rule system.
 *
 * Cloning lands on the *new* draft rather than back where it started, because
 * copying a rule set is something somebody does in order to start editing it.
 */
class RuleSetLifecycleController extends Controller
{
    /**
     * Put a rule system into play.
     */
    public function activate(
        RuleSetLifecycleRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ActivateRuleSet $activate,
    ): RedirectResponse {
        $activate->handle($request->user(), $ruleSet);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('These are the rules now.')]);

        return back();
    }

    /**
     * Put a rule system away for good.
     */
    public function archive(
        RuleSetLifecycleRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        ArchiveRuleSet $archive,
    ): RedirectResponse {
        $archive->handle($request->user(), $ruleSet);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Rule set archived.')]);

        return to_route('rules.index', [$workspace, $game, $version]);
    }

    /**
     * Copy a rule system into a fresh draft.
     */
    public function clone(
        CloneRuleSetRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        CloneRuleSet $cloneRuleSet,
    ): RedirectResponse {
        $clone = $cloneRuleSet->handle($request->user(), $ruleSet, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Rule set copied to a new draft.')]);

        return to_route('rules.show', [$workspace, $game, $version, $clone]);
    }
}
