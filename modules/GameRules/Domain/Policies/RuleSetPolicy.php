<?php

namespace Modules\GameRules\Domain\Policies;

use Illuminate\Auth\Access\Response;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\GameGrant;
use Modules\GameRules\Infrastructure\Authorization\GameAccess;
use Modules\Identity\Domain\Models\User;

/**
 * The single place rule set access is decided.
 *
 * One policy for sixteen models, which is deliberate. A rule, a mechanic, a
 * phase, a transition, an action, a requirement, a condition, a group, an
 * effect, a trigger, three kinds of outcome and a reference have no permissions
 * of their own — what decides whether any of them may be touched is whether the
 * rule set around them is still a draft, and that is one question with one
 * answer. Giving each its own policy would be fifteen copies of it, and fifteen
 * chances for the sixteenth to be forgotten.
 *
 * Every answer comes from two inputs: what the game the set ultimately belongs to
 * permits this account, and the set's own status. Nothing here reads a workspace,
 * a membership or a role — GameDesign has already turned all of that into the
 * grant this policy is written against.
 *
 * The game is always taken from the rule set rather than from the route, so "may
 * I edit these rules?" is answered against the game they actually belong to. A
 * rule set id from another game therefore fails here even if the URL names a
 * game the caller does have access to.
 *
 * Two kinds of "no" are returned, matching the convention the platform already
 * follows:
 *
 * - somebody who cannot see the game gets a 404, so rule set ids cannot be used
 *   to discover what a studio is working on;
 * - somebody who can see it but may not act gets a 403, because they already
 *   know the rule set exists and hiding it would only confuse them.
 *
 * ## Why `edit` and `rename` are separate
 *
 * They come apart on an *active* rule set, and that is section 55 of the brief.
 * An active set refuses `edit` — the rules are what a session was played under,
 * and changing them rewrites what every playtest against them means — but still
 * answers `rename`, because correcting the title of a rule system does not
 * change what was played. Archived refuses both.
 *
 * This is where the module differs most from GameEconomy's namesake, where an
 * active profile stays tunable. Tuning numbers in play is ordinary; rewriting
 * rules in play is not.
 */
class RuleSetPolicy
{
    public function __construct(private readonly GameAccess $games) {}

    /**
     * See the rule sets of a design state.
     */
    public function viewAny(User $user, GameVersion $version): Response
    {
        return $this->grantForVersion($user, $version)->allowsReading()
            ? Response::allow()
            : $this->hide();
    }

    /**
     * Start writing a rule system for a design state.
     *
     * Open to every member of the workspace, because that is what game write
     * access already means. Writing the rules down is the work; restricting who
     * may do it to the people who administer the studio would be a strange shape
     * for a design tool.
     */
    public function create(User $user, GameVersion $version): Response
    {
        $game = $version->game;

        return $game === null
            ? $this->hide()
            : $this->requireWriteAccess($this->games->grantFor($user, $game));
    }

    /**
     * Read a rule set and everything scoped to it.
     *
     * Archived sets still answer this, which is what keeps the rules a
     * convention playtest ran against legible for as long as anybody wants them.
     */
    public function view(User $user, RuleSet $ruleSet): Response
    {
        return $this->games->grantForRuleSet($user, $ruleSet)->allowsReading()
            ? Response::allow()
            : $this->hide();
    }

    /**
     * Correct a rule set's own name or description.
     *
     * Permitted on an active set, unlike everything inside it. A title is a label
     * on the document rather than part of what was played.
     */
    public function rename(User $user, RuleSet $ruleSet): Response
    {
        $decision = $this->requireWriteAccess($this->games->grantForRuleSet($user, $ruleSet));

        if (! $decision->allowed()) {
            return $decision;
        }

        return $ruleSet->isRenamable()
            ? Response::allow()
            : Response::deny($ruleSet->status->deniedReason());
    }

    /**
     * Change anything inside the rule system.
     *
     * The ability nearly every write in the module runs against: rules,
     * mechanics, phases, transitions, actions, requirements, conditions, groups,
     * effects, triggers, outcomes and references all go through here.
     *
     * Only a draft answers yes. An active set is refused with the wording that
     * tells the caller what to do instead — clone it.
     */
    public function edit(User $user, RuleSet $ruleSet): Response
    {
        $decision = $this->requireWriteAccess($this->games->grantForRuleSet($user, $ruleSet));

        if (! $decision->allowed()) {
            return $decision;
        }

        return $ruleSet->isModifiable()
            ? Response::allow()
            : Response::deny($ruleSet->status->deniedReason());
    }

    /**
     * Put a rule system into play.
     *
     * Not a privileged act — it is how a designer says "these are the rules now",
     * and a permission that made them ask somebody else would mean rule sets
     * stayed drafts. The check that does bite is structural rather than social:
     * `ActivateRuleSet` refuses while the validator reports errors.
     */
    public function activate(User $user, RuleSet $ruleSet): Response
    {
        return $this->edit($user, $ruleSet);
    }

    /**
     * Put a rule system away for good.
     *
     * Irreversible, which argues for care — but it is also the ordinary end of a
     * rule set's life, done by whoever was writing it. Permitted from active as
     * well as from draft, because retiring the rules that were in play is exactly
     * when somebody wants it.
     */
    public function archive(User $user, RuleSet $ruleSet): Response
    {
        $decision = $this->requireWriteAccess($this->games->grantForRuleSet($user, $ruleSet));

        if (! $decision->allowed()) {
            return $decision;
        }

        return $ruleSet->isArchived()
            ? Response::deny($ruleSet->status->deniedReason())
            : Response::allow();
    }

    /**
     * Copy a rule system into a fresh draft.
     *
     * Deliberately *not* gated on the source still being editable. An archived
     * rule set is exactly the thing somebody wants to clone — "start again from
     * what we shipped" is a reason to copy it, not a reason to refuse. Nothing
     * about the source changes when it is cloned, so there is nothing for its
     * status to protect.
     *
     * What is required is the right to write to the *game*, because the clone is
     * a new rule set in it.
     */
    public function clone(User $user, RuleSet $ruleSet): Response
    {
        return $this->requireWriteAccess($this->games->grantForRuleSet($user, $ruleSet));
    }

    /**
     * Destroy a rule set outright.
     *
     * No route reaches this. Rule sets are archived rather than deleted, so that
     * the rules a playtest was run under survive — and the ability is defined
     * here because the policy is the right place to have already decided that
     * nobody may.
     */
    public function delete(User $user, RuleSet $ruleSet): Response
    {
        return Response::deny(__('Rule sets are archived rather than deleted.'));
    }

    /**
     * Turn a grant into a decision about recording something.
     */
    private function requireWriteAccess(GameGrant $grant): Response
    {
        if (! $grant->allowsReading()) {
            return $this->hide();
        }

        return $grant->allowsWriting()
            ? Response::allow()
            : Response::deny($grant->deniedReason ?? __('This game is not accepting changes.'));
    }

    /**
     * Resolve the caller's standing in the game behind a design state.
     */
    private function grantForVersion(User $user, GameVersion $version): GameGrant
    {
        $game = $version->game;

        return $game === null ? GameGrant::none() : $this->games->grantFor($user, $game);
    }

    /**
     * Deny in a way that does not admit the rule set exists.
     */
    private function hide(): Response
    {
        return Response::denyAsNotFound(__('Rule set not found.'));
    }
}
