<?php

namespace Modules\Playtesting\Infrastructure\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Models\Playtest;

/**
 * The playtest policy's answers, flattened into something the client can render.
 *
 * The client needs to know whether to draw a "Complete playtest" button, and
 * the only correct source for that is the policy itself. Computing it by
 * re-reading statuses and memberships in TypeScript is what makes an
 * interface's idea of the rules drift from the server's as the rules change.
 *
 * This is a hint for the interface, not a grant. Every one of these abilities
 * is checked again on the request that actually performs the action.
 */
final class PlaytestPermissions
{
    /**
     * The abilities the client is told about for a single playtest.
     *
     * @var array<string, string>
     */
    private const ABILITIES = [
        'canView' => 'view',
        'canUpdate' => 'update',
        'canRecordConclusion' => 'recordConclusion',
        'canComplete' => 'complete',
        'canCancel' => 'cancel',
        'canCreateSession' => 'createSession',
    ];

    public function __construct(private readonly Gate $gate) {}

    /**
     * Resolve what the given account may do with the given playtest.
     *
     * @return array<string, bool>
     */
    public function for(User $user, Playtest $playtest): array
    {
        $gate = $this->gate->forUser($user);

        return array_map(
            fn (string $ability): bool => $gate->allows($ability, $playtest),
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
     * Whether the account may plan a playtest against the given game.
     *
     * Separate from the map above because it is a question about the game
     * rather than about any one playtest, and the playtests screen needs the
     * answer before there is a playtest to ask about.
     */
    public function canCreateFor(?User $user, Game $game): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->gate->forUser($user)->allows('create', [Playtest::class, $game]);
    }
}
