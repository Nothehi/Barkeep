<?php

namespace Modules\Workspace\Infrastructure\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The policy's answers, flattened into something the client can render.
 *
 * The client needs to know whether to draw a "Remove member" button, and the
 * only correct source for that is the policy itself. Computing it here — by
 * asking the gate rather than by re-reading roles — is what stops the UI's
 * idea of a role and the server's from drifting apart.
 *
 * This is a hint for the interface, not a grant. Every one of these abilities
 * is checked again on the request that actually performs the action.
 */
final class WorkspacePermissions
{
    /**
     * The abilities the client is told about.
     *
     * @var array<string, string>
     */
    private const ABILITIES = [
        'canView' => 'view',
        'canUpdate' => 'update',
        'canManageMembers' => 'manageMembers',
        'canInviteMembers' => 'inviteMembers',
        'canRemoveMembers' => 'removeMembers',
        'canChangeRoles' => 'changeMemberRole',
        'canTransferOwnership' => 'transferOwnership',
        'canArchive' => 'archive',
        'canLeave' => 'leave',
    ];

    public function __construct(private readonly Gate $gate) {}

    /**
     * Resolve what the given account may do in the given workspace.
     *
     * @return array<string, bool>
     */
    public function for(User $user, Workspace $workspace): array
    {
        $gate = $this->gate->forUser($user);

        return array_map(
            fn (string $ability): bool => $gate->allows($ability, $workspace),
            self::ABILITIES,
        );
    }

    /**
     * The all-denied map, for callers with no account.
     *
     * @return array<string, bool>
     */
    public static function none(): array
    {
        return array_fill_keys(array_keys(self::ABILITIES), false);
    }
}
