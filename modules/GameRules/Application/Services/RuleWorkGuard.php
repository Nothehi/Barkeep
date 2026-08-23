<?php

namespace Modules\GameRules\Application\Services;

use Modules\GameDesign\Application\Services\GameModificationGuard;
use Modules\GameDesign\Domain\Exceptions\GameIsNotModifiable;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameRules\Domain\Exceptions\RuleSetIsNotModifiable;
use Modules\GameRules\Domain\Models\RuleSet;

/**
 * The one place "may this still change?" is answered.
 *
 * Three things can freeze a rule system, and every write has to clear all of
 * them: the workspace may have stopped accepting changes, the game may have been
 * archived, and the rule set itself may no longer be a draft. The first two are
 * not this module's business, so they are delegated to GameDesign's own guard
 * rather than reimplemented — which stops "an archived game is read-only" from
 * having a second definition here that drifts from the first.
 *
 * The policy checks all of this before a request reaches a command, but a policy
 * only guards the HTTP door. Every command runs this too, so a caller arriving
 * another way — a console command, a queued job, a later module — cannot rewrite
 * a rule system the product considers settled.
 *
 * Having exactly one implementation is the point. This module has more writable
 * child records than any other in the platform: rules, mechanics, phases,
 * transitions, actions, requirements, conditions, groups, memberships, effects,
 * triggers, three kinds of outcome and references all pass through here, and
 * "only a draft is editable" spread across forty commands is forty chances to
 * forget it in the forty-first.
 *
 * ## Why there are two questions rather than one
 *
 * {@see ensureRuleSetIsRenamable()} and {@see ensureRuleSetAcceptsChanges()} come
 * apart on an *active* rule set, and that separation is section 55 of the brief.
 * Correcting the title of the rule system a session was played under does not
 * change what was played; rewriting one of its rules does. So an active set
 * accepts the first and refuses the second, with a message that names the way
 * forward — clone it.
 */
final class RuleWorkGuard
{
    public function __construct(private readonly GameModificationGuard $games) {}

    /**
     * Require that the given game may still have rules written on it.
     *
     * @throws GameIsNotModifiable
     */
    public function ensureGameAcceptsRuleWork(Game $game): void
    {
        $this->games->ensureGameIsModifiable($game);
    }

    /**
     * Require that a rule set's own name and description may still be changed.
     *
     * The game is checked first: if the project is closed, saying so is more
     * useful than complaining about the rule set inside it.
     *
     * @throws RuleSetIsNotModifiable
     */
    public function ensureRuleSetIsRenamable(RuleSet $ruleSet): void
    {
        $this->ensureGameOfRuleSetIsOpen($ruleSet);

        if (! $ruleSet->isRenamable()) {
            throw RuleSetIsNotModifiable::forStatus($ruleSet->status);
        }
    }

    /**
     * Require that the rules inside a set may still be changed.
     *
     * Only a draft passes. This is the single enforcement point for the module's
     * central rule, and every one of the forty-odd write commands calls it.
     *
     * @throws RuleSetIsNotModifiable
     */
    public function ensureRuleSetAcceptsChanges(RuleSet $ruleSet): void
    {
        $this->ensureGameOfRuleSetIsOpen($ruleSet);

        if (! $ruleSet->isModifiable()) {
            throw RuleSetIsNotModifiable::forStatus($ruleSet->status);
        }
    }

    /**
     * Require that a rule set may still move through its lifecycle.
     *
     * Looser than accepting changes, because archiving an active set is the
     * ordinary end of its life and activating a draft is the ordinary middle. An
     * already-archived set is refused; the transition matrix decides the rest.
     *
     * @throws RuleSetIsNotModifiable
     */
    public function ensureRuleSetAcceptsLifecycleChange(RuleSet $ruleSet): void
    {
        $this->ensureGameOfRuleSetIsOpen($ruleSet);

        if ($ruleSet->isArchived()) {
            throw RuleSetIsNotModifiable::forStatus($ruleSet->status);
        }
    }

    /**
     * Require that a rule set may be copied.
     *
     * Only the game is checked. An archived rule set is exactly the thing
     * somebody wants to clone — "start again from what we shipped" is a reason to
     * copy it, not a reason to refuse — and nothing about the source changes when
     * it is copied, so there is nothing for its status to protect.
     *
     * @throws RuleSetIsNotModifiable
     */
    public function ensureRuleSetCanBeCloned(RuleSet $ruleSet): void
    {
        $this->ensureGameOfRuleSetIsOpen($ruleSet);
    }

    /**
     * Require that the game a rule set belongs to is still open.
     *
     * Silently permits a set whose game cannot be reached, which is the same
     * choice every guard in the platform makes: the relation is unloaded rather
     * than absent in practice, and refusing on a lazy-load miss would turn a
     * performance detail into a rule.
     *
     * @throws RuleSetIsNotModifiable
     */
    private function ensureGameOfRuleSetIsOpen(RuleSet $ruleSet): void
    {
        $game = $ruleSet->version?->game;

        if ($game === null) {
            return;
        }

        try {
            $this->games->ensureGameIsModifiable($game);
        } catch (GameIsNotModifiable $refusal) {
            throw RuleSetIsNotModifiable::becauseGameIsClosed($refusal->getMessage());
        }
    }
}
