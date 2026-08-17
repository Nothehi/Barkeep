<?php

namespace Modules\DesignFramework\Infrastructure\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Modules\DesignFramework\Domain\ValueObjects\GameGrant;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;

/**
 * The one place this module decides what a game permits.
 *
 * DesignFramework sits on top of GameDesign, which sits on top of Workspace. That
 * stack is exactly why this file exists: the question "may this person record
 * framework work here?" already has an answer, and it is GameDesign's game policy
 * — which has itself already accounted for workspace membership, the workspace's
 * status and the game's own. Asking any of those again in this module would be a
 * second implementation of the tenancy rules, and a second implementation is a
 * second answer waiting to disagree with the first.
 *
 * So this asks the gate rather than reading roles. Two abilities, translated into
 * the two booleans the module's own rules are written in terms of:
 *
 * - `view` on the game becomes the right to read its framework progress.
 *   Archiving a game does not revoke it, which keeps the assessment of a shelved
 *   design legible.
 * - `update` on the game becomes the right to record against it. Archiving a game
 *   does revoke that, and the refusal carries GameDesign's own wording so
 *   somebody is told *why* rather than just no.
 *
 * An architecture test holds the line: nothing else in DesignFramework reads a
 * workspace role, and nothing else asks the gate about a game.
 */
final class GameAccess
{
    public function __construct(private readonly Gate $gate) {}

    /**
     * Resolve what the given account may do with the given game's framework work.
     *
     * Reading is checked first and short-circuits. Somebody who cannot see the
     * game gets the empty grant, which the policy turns into a 404 — a framework
     * adoption must not confirm that a game exists to somebody who was not
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
