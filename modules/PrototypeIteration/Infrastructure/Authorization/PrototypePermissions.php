<?php

namespace Modules\PrototypeIteration\Infrastructure\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Domain\Models\Prototype;

/**
 * The prototype policy's answers, flattened into something the client can render.
 *
 * The client needs to know whether to draw an "Archive prototype" button, and the
 * only correct source for that is the policy itself. Computing it by re-reading
 * statuses and memberships in TypeScript is what makes an interface's idea of the
 * rules drift from the server's as the rules change.
 *
 * This is a hint for the interface, not a grant. Every one of these abilities is
 * checked again on the request that actually performs the action.
 */
final class PrototypePermissions
{
    /**
     * The abilities the client is told about for a single prototype.
     *
     * @var array<string, string>
     */
    private const ABILITIES = [
        'canView' => 'view',
        'canUpdate' => 'update',
        'canArchive' => 'archive',
        'canCreateVersion' => 'createVersion',
    ];

    public function __construct(private readonly Gate $gate) {}

    /**
     * Resolve what the given account may do with the given prototype.
     *
     * @return array<string, bool>
     */
    public function for(User $user, Prototype $prototype): array
    {
        $gate = $this->gate->forUser($user);

        return array_map(
            fn (string $ability): bool => $gate->allows($ability, $prototype),
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
     * Whether the account may start a prototype for the given game.
     *
     * Separate from the map above because it is a question about the game rather
     * than about any one prototype, and the prototypes screen needs the answer
     * before there is a prototype to ask about.
     */
    public function canCreateFor(?User $user, Game $game): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->gate->forUser($user)->allows('create', [Prototype::class, $game]);
    }
}
