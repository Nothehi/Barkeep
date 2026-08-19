<?php

namespace Modules\GameEconomy\Infrastructure\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Domain\ValueObjects\GameGrant;
use Modules\Identity\Domain\Models\User;

/**
 * The one place GameEconomy decides what a game permits.
 *
 * This module sits on top of GameDesign, which sits on top of Workspace. That
 * stack is exactly why this file exists: "may this person tune this game's
 * economy?" already has an answer, and it is GameDesign's game policy — which
 * has itself already accounted for workspace membership, the workspace's status
 * and the game's own. Asking any of those again here would be a second
 * implementation of the tenancy rules, and a second implementation is a second
 * answer waiting to disagree with the first.
 *
 * So this asks the gate rather than reading roles. Two abilities, translated
 * into the two booleans this module's rules are written in terms of:
 *
 * - `view` on the game becomes the right to read its balance configuration.
 *   Archiving a game does not revoke it, which is what keeps the numbers a
 *   convention playtest ran against legible for as long as anybody wants them.
 * - `update` on the game becomes the right to tune it. Archiving does revoke
 *   that, and the refusal carries GameDesign's own wording so somebody is told
 *   *why* rather than just no.
 *
 * An architecture test holds the line: nothing else in GameEconomy reads a
 * workspace role, and nothing else asks the gate about a game.
 */
final class GameAccess
{
    public function __construct(private readonly Gate $gate) {}

    /**
     * Resolve what the given account may do with the economy of a game.
     *
     * Reading is checked first and short-circuits. Somebody who cannot see the
     * game gets the empty grant, which the policies turn into a 404 — a balance
     * profile must not confirm that a game exists to somebody who was not
     * allowed to know.
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
     * Resolve what the given account may do with a particular configuration.
     *
     * Walks profile → version → game, which is the chain every answer in this
     * module ultimately rests on. A profile whose version or game cannot be
     * reached gets the empty grant rather than an optimistic one: the safe
     * reading of a broken chain is that nobody may see it.
     */
    public function grantForProfile(User $user, BalanceProfile $profile): GameGrant
    {
        $game = $this->gameOf($profile);

        return $game === null ? GameGrant::none() : $this->grantFor($user, $game);
    }

    /**
     * The game a configuration ultimately belongs to.
     */
    public function gameOf(BalanceProfile $profile): ?Game
    {
        return $profile->version?->game;
    }
}
