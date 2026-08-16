<?php

namespace Modules\Playtesting\Domain\Events;

use Modules\Playtesting\Domain\Enums\PlaytestParticipantRole;

/**
 * Dispatched when somebody joins a session.
 *
 * The account id is null for the majority of participants, who have no Barkeep
 * account at all. A consumer that only cares about registered people — say,
 * crediting somebody for helping test a friend's game — should skip those
 * rather than try to resolve them.
 */
final readonly class ParticipantAdded
{
    public function __construct(
        public string $participantId,
        public string $sessionId,
        public string $playtestId,
        public string $gameId,
        public ?string $userId,
        public PlaytestParticipantRole $role,
        public string $addedBy,
    ) {}
}
