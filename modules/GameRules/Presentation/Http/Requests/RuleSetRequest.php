<?php

namespace Modules\GameRules\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Domain\Models\ConditionGroup;
use Modules\GameRules\Domain\Models\ConditionGroupCondition;
use Modules\GameRules\Domain\Models\DefeatCondition;
use Modules\GameRules\Domain\Models\GameEndCondition;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\PhaseTransition;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Domain\Models\RuleEffect;
use Modules\GameRules\Domain\Models\RuleMechanic;
use Modules\GameRules\Domain\Models\RuleReference;
use Modules\GameRules\Domain\Models\RuleRequirement;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\Models\RuleTrigger;
use Modules\GameRules\Domain\Models\VictoryCondition;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;
use RuntimeException;

/**
 * The shared authorization plumbing for rules requests.
 *
 * Two rules hold for every subclass, and together they are the module's whole
 * defence against rewriting somebody else's rules:
 *
 * - the workspace, the game, the design version, the rule set and every child
 *   record come from resolved route bindings, never from the request body, so a
 *   caller does not get to name what their permissions are checked against;
 * - the answer is the policy's {@see Response}, not a boolean, so its choice
 *   between "you may not" and "there is no such thing" survives all the way to
 *   the status code.
 *
 * The bindings themselves are chained — see `GameRulesServiceProvider` — so a rule
 * set belonging to another design state, or a phase belonging to another rule
 * system, fails to resolve before any of this runs.
 *
 * Eight identifiers escape that arrangement because they have no route segment of
 * their own: a rule's or phase's parent, the phase a rule or action happens in,
 * both ends of a transition, the condition and trigger that guard one, the owner
 * of a requirement or effect, and the condition an outcome is measured by. Every
 * one is checked explicitly, by rule objects that resolve it through the rule set
 * that owns it — which is why those rules exist at all, and why none of them is an
 * `exists` clause.
 *
 * Nearly every write in this module authorizes against `edit`. "May the rules
 * inside this set be changed?" is one question with one answer, and asking it once
 * is what stops sixteen kinds of record from drifting apart as the rules change.
 * {@see inspectRename()} is the deliberate exception, and the one place the module
 * treats an active rule set as still writable.
 */
abstract class RuleSetRequest extends FormRequest
{
    /**
     * The workspace this request is scoped to.
     */
    protected function workspace(): Workspace
    {
        $workspace = $this->route('workspace');

        if (! $workspace instanceof Workspace) {
            throw new RuntimeException(static::class.' was used on a route without a bound workspace.');
        }

        return $workspace;
    }

    /**
     * The game this request is about.
     */
    protected function game(): Game
    {
        $game = $this->route('game');

        if (! $game instanceof Game) {
            throw new RuntimeException(static::class.' was used on a route without a bound game.');
        }

        return $game;
    }

    /**
     * The design state this request is about.
     */
    protected function version(): GameVersion
    {
        $version = $this->route('version');

        if (! $version instanceof GameVersion) {
            throw new RuntimeException(static::class.' was used on a route without a bound game version.');
        }

        return $version;
    }

    /**
     * The rule system this request is about.
     */
    protected function ruleSet(): RuleSet
    {
        $ruleSet = $this->route('ruleSet');

        if ($ruleSet instanceof RuleSet) {
            return $ruleSet;
        }

        $rule = $this->route('gameRule');

        if ($rule instanceof GameRule && $rule->ruleSet !== null) {
            return $rule->ruleSet;
        }

        $group = $this->route('conditionGroup');

        if ($group instanceof ConditionGroup && $group->ruleSet !== null) {
            return $group->ruleSet;
        }

        throw new RuntimeException(static::class.' was used on a route without a bound rule set.');
    }

    /**
     * The rule this request is about.
     */
    protected function gameRule(): GameRule
    {
        $rule = $this->route('gameRule');

        if (! $rule instanceof GameRule) {
            throw new RuntimeException(static::class.' was used on a route without a bound rule.');
        }

        return $rule;
    }

    /**
     * The mechanism this request is about.
     */
    protected function ruleMechanic(): RuleMechanic
    {
        $mechanic = $this->route('ruleMechanic');

        if (! $mechanic instanceof RuleMechanic) {
            throw new RuntimeException(static::class.' was used on a route without a bound mechanic.');
        }

        return $mechanic;
    }

    /**
     * The stage of play this request is about.
     */
    protected function gamePhase(): GamePhase
    {
        $phase = $this->route('gamePhase');

        if (! $phase instanceof GamePhase) {
            throw new RuntimeException(static::class.' was used on a route without a bound phase.');
        }

        return $phase;
    }

    /**
     * The transition this request is about.
     */
    protected function transition(): PhaseTransition
    {
        $transition = $this->route('transition');

        if (! $transition instanceof PhaseTransition) {
            throw new RuntimeException(static::class.' was used on a route without a bound transition.');
        }

        return $transition;
    }

    /**
     * The player action this request is about.
     */
    protected function ruleAction(): RuleAction
    {
        $action = $this->route('ruleAction');

        if (! $action instanceof RuleAction) {
            throw new RuntimeException(static::class.' was used on a route without a bound action.');
        }

        return $action;
    }

    /**
     * The requirement this request is about.
     */
    protected function requirement(): RuleRequirement
    {
        $requirement = $this->route('requirement');

        if (! $requirement instanceof RuleRequirement) {
            throw new RuntimeException(static::class.' was used on a route without a bound requirement.');
        }

        return $requirement;
    }

    /**
     * The condition this request is about.
     */
    protected function ruleCondition(): RuleCondition
    {
        $condition = $this->route('ruleCondition');

        if (! $condition instanceof RuleCondition) {
            throw new RuntimeException(static::class.' was used on a route without a bound condition.');
        }

        return $condition;
    }

    /**
     * The condition group this request is about.
     */
    protected function conditionGroup(): ConditionGroup
    {
        $group = $this->route('conditionGroup');

        if (! $group instanceof ConditionGroup) {
            throw new RuntimeException(static::class.' was used on a route without a bound condition group.');
        }

        return $group;
    }

    /**
     * The membership this request is about.
     */
    protected function membership(): ConditionGroupCondition
    {
        $membership = $this->route('membership');

        if (! $membership instanceof ConditionGroupCondition) {
            throw new RuntimeException(static::class.' was used on a route without a bound group membership.');
        }

        return $membership;
    }

    /**
     * The effect this request is about.
     *
     * The route parameter is `{ruleEffect}` rather than `{effect}` because
     * GameEconomy already binds the shorter name, and route binder names are
     * global to the application — see `.ai/rules/providers.md`.
     */
    protected function ruleEffect(): RuleEffect
    {
        $effect = $this->route('ruleEffect');

        if (! $effect instanceof RuleEffect) {
            throw new RuntimeException(static::class.' was used on a route without a bound effect.');
        }

        return $effect;
    }

    /**
     * The trigger this request is about.
     */
    protected function trigger(): RuleTrigger
    {
        $trigger = $this->route('trigger');

        if (! $trigger instanceof RuleTrigger) {
            throw new RuntimeException(static::class.' was used on a route without a bound trigger.');
        }

        return $trigger;
    }

    /**
     * The victory condition this request is about.
     */
    protected function victoryCondition(): VictoryCondition
    {
        $outcome = $this->route('victoryCondition');

        if (! $outcome instanceof VictoryCondition) {
            throw new RuntimeException(static::class.' was used on a route without a bound victory condition.');
        }

        return $outcome;
    }

    /**
     * The defeat condition this request is about.
     */
    protected function defeatCondition(): DefeatCondition
    {
        $outcome = $this->route('defeatCondition');

        if (! $outcome instanceof DefeatCondition) {
            throw new RuntimeException(static::class.' was used on a route without a bound defeat condition.');
        }

        return $outcome;
    }

    /**
     * The end condition this request is about.
     */
    protected function endCondition(): GameEndCondition
    {
        $outcome = $this->route('endCondition');

        if (! $outcome instanceof GameEndCondition) {
            throw new RuntimeException(static::class.' was used on a route without a bound end condition.');
        }

        return $outcome;
    }

    /**
     * The rule reference this request is about.
     */
    protected function reference(): RuleReference
    {
        $reference = $this->route('reference');

        if (! $reference instanceof RuleReference) {
            throw new RuntimeException(static::class.' was used on a route without a bound rule reference.');
        }

        return $reference;
    }

    /**
     * The signed in account.
     */
    protected function actor(): ?User
    {
        $user = $this->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * Run an ability against the policy, with whatever it is about.
     *
     * @param  array<int, mixed>  $arguments
     */
    protected function inspect(string $ability, array $arguments): Response
    {
        $user = $this->actor();

        if ($user === null) {
            return Response::deny();
        }

        return Gate::forUser($user)->inspect($ability, $arguments);
    }

    /**
     * Run a rule set ability against the bound design state.
     */
    protected function inspectVersion(string $ability): Response
    {
        return $this->inspect($ability, [RuleSet::class, $this->version()]);
    }

    /**
     * Require the right to change the rules this request is inside.
     *
     * The ability nearly every write in the module runs against. Only a draft
     * answers yes; an active rule set is refused with the wording that tells the
     * caller to clone it.
     */
    protected function inspectEdit(): Response
    {
        return $this->inspect('edit', [$this->ruleSet()]);
    }

    /**
     * Require the right to correct the rule set's own title.
     *
     * The one ability an *active* rule set still answers. A title is a label on
     * the document; the rules are what a session was played under.
     */
    protected function inspectRename(): Response
    {
        return $this->inspect('rename', [$this->ruleSet()]);
    }

    /**
     * Run a lifecycle ability against the bound rule set.
     */
    protected function inspectRuleSet(string $ability): Response
    {
        return $this->inspect($ability, [$this->ruleSet()]);
    }
}
