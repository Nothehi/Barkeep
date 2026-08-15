<?php

namespace Modules\GameDesign\Infrastructure\Authorization;

use Modules\GameDesign\Domain\ValueObjects\WorkspaceGrant;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Enums\WorkspaceRole;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The one place GameDesign reads Workspace.
 *
 * GameDesign is built on the tenancy boundary Workspace owns, but it must not
 * own a second copy of it. Everything this module needs to know about a
 * workspace is translated here, once, into a {@see WorkspaceGrant} — three
 * booleans its own rules are written in terms of.
 *
 * Keeping the translation in a single class is what makes the dependency
 * intentional rather than ambient. When Workspace grows a fourth role or a
 * per-context permission, this file learns about it and nothing else in
 * GameDesign has to. An architecture test holds that line.
 *
 * Membership is resolved through the workspace model's own memo, so asking
 * for a grant repeatedly while rendering a list of games costs one query.
 */
final class WorkspaceAccess
{
    /**
     * Resolve what the given account may do inside the given workspace.
     *
     * "May administer" maps to the roles that run a workspace. It is used for
     * the heavier game actions — archiving one, and deleting one if that is
     * ever exposed — while ordinary design work is open to every member,
     * which is what a shared studio actually looks like.
     */
    public function grantFor(User $user, Workspace $workspace): WorkspaceGrant
    {
        $member = $workspace->memberFor($user);

        if ($member === null) {
            return WorkspaceGrant::none();
        }

        $isReadable = $workspace->status->isReadable();
        $allowsChanges = $workspace->isModifiable();

        return new WorkspaceGrant(
            isMember: true,
            isReadable: $isReadable,
            allowsChanges: $allowsChanges,
            canAdminister: $member->role->atLeast(WorkspaceRole::Admin),
            deniedReason: $allowsChanges ? null : $workspace->status->deniedReason(),
        );
    }
}
