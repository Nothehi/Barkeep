<?php

namespace Modules\Playtesting\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Application\Services\PlaytestModificationGuard;
use Modules\Playtesting\Domain\Events\ParticipantRemoved;
use Modules\Playtesting\Domain\Exceptions\ParticipantDoesNotBelongToSession;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;
use Modules\Playtesting\Domain\Models\PlaytestSession;

/**
 * Take somebody off a session.
 *
 * Usually a correction rather than a departure — the wrong name was typed, or
 * somebody was added twice. Anyone who genuinely left part way through has a
 * `left_at` instead, which keeps them in the record where they belong.
 *
 * What survives removal is the important part. Observations made about the
 * participant and feedback they gave stay, with their attribution dropped by
 * the foreign keys. Deleting the evidence along with the person would mean a
 * mistyped name could quietly destroy what somebody said, which is a far worse
 * outcome than an unattributed comment.
 */
final class RemoveParticipant
{
    public function __construct(private readonly PlaytestModificationGuard $guard) {}

    public function handle(User $actor, PlaytestSession $session, PlaytestParticipant $participant): void
    {
        $this->guard->ensureSessionAcceptsEvidence($session);

        if (! $participant->belongsToSession($session)) {
            throw ParticipantDoesNotBelongToSession::forPair($participant->getKey(), $session->getKey());
        }

        $userId = $participant->user_id;
        $participantId = $participant->getKey();

        $participant->delete();

        event(new ParticipantRemoved(
            participantId: $participantId,
            sessionId: $session->getKey(),
            playtestId: $session->playtest_id,
            gameId: $session->playtest->game_id ?? '',
            userId: $userId,
            removedBy: $actor->id,
        ));
    }
}
