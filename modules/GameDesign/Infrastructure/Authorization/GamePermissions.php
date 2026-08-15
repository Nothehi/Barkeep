<?php

namespace Modules\GameDesign\Infrastructure\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * The game policy's answers, flattened into something the client can render.
 *
 * The client needs to know whether to draw an "Archive game" button, and the
 * only correct source for that is the policy itself. Computing it by asking
 * the gate — rather than by re-reading roles and statuses in TypeScript — is
 * what stops the interface's idea of the rules and the server's from drifting
 * apart as the rules change.
 *
 * This is a hint for the interface, not a grant. Every one of these abilities
 * is checked again on the request that actually performs the action.
 */
final class GamePermissions
{
    /**
     * The abilities the client is told about for a single game.
     *
     * @var array<string, string>
     */
    private const ABILITIES = [
        'canView' => 'view',
        'canUpdate' => 'update',
        'canChangeStatus' => 'changeStatus',
        'canChangeDesignPhase' => 'changeDesignPhase',
        'canArchive' => 'archive',
        'canCreateVersion' => 'createVersion',
    ];

    public function __construct(private readonly Gate $gate) {}

    /**
     * Resolve what the given account may do with the given game.
     *
     * @return array<string, bool>
     */
    public function for(User $user, Game $game): array
    {
        $gate = $this->gate->forUser($user);

        return array_map(
            fn (string $ability): bool => $gate->allows($ability, $game),
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

    /**
     * Whether the account may start a game in the given workspace.
     *
     * Separate from the map above because it is a question about the
     * workspace rather than about any one game, and the games screen needs
     * the answer before there is a game to ask about.
     */
    public function canCreateIn(?User $user, Workspace $workspace): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->gate->forUser($user)->allows('create', [Game::class, $workspace]);
    }
}
