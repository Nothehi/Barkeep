<?php

namespace Modules\Playtesting\Infrastructure\Workspace;

use Illuminate\Support\Collection;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Infrastructure\Authorization\GameAccess;
use Modules\Workspace\Application\Queries\GetWorkspaceMembers;
use Modules\Workspace\Domain\Models\WorkspaceMember;

/**
 * The one place Playtesting asks who is on a studio's team.
 *
 * It exists for a single question, and the question is a security one. A
 * participant may be linked to a Barkeep account, and that account id arrives
 * in a request body — so without a check, anybody could seat an arbitrary
 * account at their own session and read back that person's name and email
 * through the participant resource.
 *
 * Restricting the link to people who already share the workspace closes that
 * completely: seating somebody discloses nothing the caller could not already
 * see on the members screen. Everybody else is recorded the way most
 * participants are anyway — by display name, as a guest — which loses nothing,
 * because a stranger's Barkeep account was never the interesting fact about
 * them.
 *
 * Roles are read here and nowhere else in the module, and only to answer
 * "is this person on the team at all". What they may *do* is GameDesign's
 * policy's business, resolved through {@see GameAccess}.
 * An architecture test holds that line.
 */
final class WorkspaceRoster
{
    public function __construct(private readonly GetWorkspaceMembers $members) {}

    /**
     * Determine whether an account shares the workspace a game belongs to.
     */
    public function isTeammate(Game $game, string $userId): bool
    {
        return $this->memberships($game)
            ->contains(fn (WorkspaceMember $member): bool => $member->user_id === $userId);
    }

    /**
     * The accounts that may be linked to a participant of this game's playtests.
     *
     * Handed to the client so the "add participant" form can offer the team by
     * name instead of asking somebody to paste an identifier. Anyone not on
     * this list is still perfectly welcome at the session — they are recorded
     * as a guest, which is what most participants are.
     *
     * @return Collection<int, User>
     */
    public function candidatesFor(Game $game): Collection
    {
        return $this->memberships($game)
            ->map(fn (WorkspaceMember $member): ?User => $member->user)
            ->filter()
            ->values();
    }

    /**
     * The memberships of the workspace a game belongs to.
     *
     * @return Collection<int, WorkspaceMember>
     */
    private function memberships(Game $game): Collection
    {
        $workspace = $game->workspace;

        return $workspace === null
            ? collect()
            : $this->members->handle($workspace);
    }
}
