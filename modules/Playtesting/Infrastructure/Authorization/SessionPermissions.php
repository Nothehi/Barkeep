<?php

namespace Modules\Playtesting\Infrastructure\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Domain\Models\PlaytestSession;

/**
 * The session policy's answers, flattened for the client.
 *
 * Longer than the playtest map because a live session screen offers more, and
 * because the evidence abilities come apart from the lifecycle ones: a session
 * that has ended still shows its participants and observations, but offers no
 * way to add any.
 *
 * A hint for the interface, not a grant. Every ability here is checked again
 * on the request that performs the action.
 */
final class SessionPermissions
{
    /**
     * The abilities the client is told about for a single session.
     *
     * @var array<string, string>
     */
    private const ABILITIES = [
        'canView' => 'view',
        'canUpdate' => 'update',
        'canStart' => 'start',
        'canComplete' => 'complete',
        'canCancel' => 'cancel',
        'canManageParticipants' => 'manageParticipants',
        'canCreateObservation' => 'createObservation',
        'canManageObservations' => 'manageObservations',
        'canCreateFeedback' => 'createFeedback',
        'canManageFeedback' => 'manageFeedback',
    ];

    public function __construct(private readonly Gate $gate) {}

    /**
     * Resolve what the given account may do with the given session.
     *
     * @return array<string, bool>
     */
    public function for(User $user, PlaytestSession $session): array
    {
        $gate = $this->gate->forUser($user);

        return array_map(
            fn (string $ability): bool => $gate->allows($ability, $session),
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
