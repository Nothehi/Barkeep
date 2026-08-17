<?php

namespace Modules\DesignFramework\Infrastructure\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\GameDesign\Domain\Models\Game;
use Modules\Identity\Domain\Models\User;

/**
 * The game framework policy's answers, flattened into something the client can render.
 *
 * `canRecordProgress` is the one that matters, and it covers all four kinds of write: an
 * evaluation, a practice completion, a checklist tick and a prompt answer. One flag rather
 * than four, because the policy grants them together — and giving the client four would
 * invite a screen to disable the checkboxes while leaving the rating buttons live.
 *
 * These are hints for the interface, not grants. Every one is checked again on the request
 * that performs the action.
 */
final class GameFrameworkPermissions
{
    /**
     * The abilities the client is told about for a single adoption.
     *
     * @var array<string, string>
     */
    private const ABILITIES = [
        'canView' => 'view',
        'canRecordProgress' => 'recordProgress',
        'canPause' => 'pause',
        'canResume' => 'resume',
        'canComplete' => 'complete',
    ];

    public function __construct(private readonly Gate $gate) {}

    /**
     * Resolve what the given account may do with the given adoption.
     *
     * @return array<string, bool>
     */
    public function for(User $user, GameFramework $adoption): array
    {
        $gate = $this->gate->forUser($user);

        return array_map(
            fn (string $ability): bool => $gate->allows($ability, $adoption),
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
     * Whether the account may adopt a framework for the given game.
     *
     * Separate from the map above because it is a question about the game rather than about
     * any adoption, and the framework screen needs the answer precisely when there is no
     * adoption yet.
     */
    public function canAssignFor(?User $user, Game $game): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->gate->forUser($user)->allows('assign', [GameFramework::class, $game]);
    }
}
