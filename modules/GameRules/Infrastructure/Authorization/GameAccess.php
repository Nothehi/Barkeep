<?php

namespace Modules\GameRules\Infrastructure\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\ValueObjects\GameGrant;
use Modules\Identity\Domain\Models\User;

/**
 * The one place GameRules decides what a game permits.
 *
 * This module sits on top of GameDesign, which sits on top of Workspace. That
 * stack is exactly why this file exists: "may this person write this game's
 * rules?" already has an answer, and it is GameDesign's game policy — which has
 * itself already accounted for workspace membership, the workspace's status and
 * the game's own. Asking any of those again here would be a second
 * implementation of the tenancy rules, and a second implementation is a second
 * answer waiting to disagree with the first.
 *
 * So this asks the gate rather than reading roles. Two abilities, translated into
 * the two booleans this module's rules are written in terms of:
 *
 * - `view` on the game becomes the right to read its rules. Archiving a game does
 *   not revoke it, which is what keeps the rules a convention playtest ran under
 *   legible for as long as anybody wants them.
 * - `update` on the game becomes the right to write them. Archiving does revoke
 *   that, and the refusal carries GameDesign's own wording so somebody is told
 *   *why* rather than just no.
 *
 * An architecture test holds the line: nothing else in GameRules reads a
 * workspace role, and nothing else asks the gate about a game.
 */
final class GameAccess
{
    public function __construct(private readonly Gate $gate) {}

    /**
     * Resolve what the given account may do with the rules of a game.
     *
     * Reading is checked first and short-circuits. Somebody who cannot see the
     * game gets the empty grant, which the policy turns into a 404 — a rule set
     * must not confirm that a game exists to somebody who was not allowed to
     * know.
     */
    public function grantFor(User $user, Game $game): GameGrant
    {
        $gate = $this->gate->forUser($user);

        if (! $gate->allows('view', $game)) {
            return GameGrant::none();
        }

        $write = $gate->inspect('update', $game);

        return new GameGrant(
            canRead: true,
            canWrite: $write->allowed(),
            deniedReason: $write->allowed() ? null : $write->message(),
        );
    }

    /**
     * Resolve what the given account may do with a particular rule system.
     *
     * Walks rule set → version → game, which is the chain every answer in this
     * module ultimately rests on. A set whose version or game cannot be reached
     * gets the empty grant rather than an optimistic one: the safe reading of a
     * broken chain is that nobody may see it.
     */
    public function grantForRuleSet(User $user, RuleSet $ruleSet): GameGrant
    {
        $game = $this->gameOf($ruleSet);

        return $game === null ? GameGrant::none() : $this->grantFor($user, $game);
    }

    /**
     * The game a rule system ultimately belongs to.
     */
    public function gameOf(RuleSet $ruleSet): ?Game
    {
        return $ruleSet->version?->game;
    }
}
