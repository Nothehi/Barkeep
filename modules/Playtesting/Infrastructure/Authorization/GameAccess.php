<?php

namespace Modules\Playtesting\Infrastructure\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\ValueObjects\GameGrant;

/**
 * The one place Playtesting decides what a game permits.
 *
 * Playtesting sits on top of GameDesign, which sits on top of Workspace. That
 * stack is exactly why this file exists: the question "may this person record
 * a playtest here?" already has an answer, and it is GameDesign's game policy —
 * which has itself already accounted for workspace membership, the workspace's
 * status and the game's own. Asking any of those again in this module would be
 * a second implementation of the tenancy rules, and a second implementation is
 * a second answer waiting to disagree with the first.
 *
 * So this asks the gate rather than reading roles. Two abilities, translated
 * into the two booleans Playtesting's own rules are written in terms of:
 *
 * - `view` on the game becomes the right to read its playtests. Archiving a
 *   game does not revoke it, which is what keeps historical evidence legible
 *   after the design has moved on.
 * - `update` on the game becomes the right to record against it. Archiving a
 *   game does revoke that, and the refusal carries GameDesign's own wording so
 *   somebody is told *why* rather than just no.
 *
 * An architecture test holds the line: nothing else in Playtesting reads a
 * workspace role, and nothing else asks the gate about a game.
 */
final class GameAccess
{
    public function __construct(private readonly Gate $gate) {}

    /**
     * Resolve what the given account may do with playtests of the given game.
     *
     * Reading is checked first and short-circuits. Somebody who cannot see the
     * game gets the empty grant, which the policies turn into a 404 — a
     * playtest must not confirm that a game exists to somebody who was not
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
}
