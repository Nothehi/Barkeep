<?php

namespace Modules\GameRules\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Application\Commands\CreateRuleReference;
use Modules\GameRules\Application\Commands\DeleteRuleReference;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\RuleReference;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Presentation\Http\Requests\CreateRuleReferenceRequest;
use Modules\GameRules\Presentation\Http\Requests\StructureChangeRequest;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * How the rules relate to one another.
 *
 * Nested under the rule doing the referring, which is what makes the pairing check
 * possible: the referenced rule is resolved through that rule's own set, and
 * `rule_references` carries no rule set of its own.
 */
class RuleReferenceController extends Controller
{
    /**
     * Say that one rule depends on, modifies, overrides or excepts another.
     */
    public function store(
        CreateRuleReferenceRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GameRule $gameRule,
        CreateRuleReference $createReference,
    ): RedirectResponse {
        $createReference->handle($request->user(), $gameRule, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reference added.')]);

        return back();
    }

    /**
     * Withdraw the claim that two rules are connected.
     */
    public function destroy(
        StructureChangeRequest $request,
        Workspace $workspace,
        Game $game,
        GameVersion $version,
        RuleSet $ruleSet,
        GameRule $gameRule,
        RuleReference $reference,
        DeleteRuleReference $deleteReference,
    ): RedirectResponse {
        $deleteReference->handle($request->user(), $reference);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reference removed.')]);

        return back();
    }
}
