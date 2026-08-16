<?php

namespace Modules\Playtesting\Application\Commands;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Application\DTOs\AddParticipantData;
use Modules\Playtesting\Application\Services\PlaytestModificationGuard;
use Modules\Playtesting\Domain\Events\ParticipantAdded;
use Modules\Playtesting\Domain\Exceptions\ParticipantAccountIsNotOnTheTeam;
use Modules\Playtesting\Domain\Exceptions\ParticipantIsAlreadyInSession;
use Modules\Playtesting\Domain\Models\PlaytestParticipant;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Infrastructure\Workspace\WorkspaceRoster;

/**
 * Seat somebody at a session.
 *
 * The command has to be fast to reach and forgiving, because it is used while
 * people are sitting down. A name is enough.
 *
 * ## Accounts are optional, and restricted when given
 *
 * Most participants have no Barkeep account: they are a friend, somebody from
 * the local game group, a stranger at a convention. Creating shadow Identity
 * users for them would put real people into the platform's user table without
 * their knowledge, so `user_id` stays null and the display name carries the
 * record.
 *
 * When an account *is* given it must belong to the workspace. That is not a
 * rule about who may play — anyone may play, as a guest — but about
 * disclosure: linking an account makes its name and address readable through
 * the participant list, so it has to be one the caller could already see.
 *
 * ## Two facilitators adding the same person
 *
 * Both are looking at the same session on their own devices, so this races in
 * practice. The unique index on (session_id, user_id) decides it: one insert
 * wins, the other comes back as a violation and is reported as "already here"
 * rather than as an error. Guests are outside that constraint on purpose —
 * two people introduced as "Sam" may genuinely be two people, and the platform
 * has nothing to tell them apart with.
 */
final class AddParticipant
{
    public function __construct(
        private readonly PlaytestModificationGuard $guard,
        private readonly WorkspaceRoster $roster,
    ) {}

    public function handle(User $actor, PlaytestSession $session, AddParticipantData $data): PlaytestParticipant
    {
        $this->guard->ensureSessionAcceptsEvidence($session);

        if ($data->userId !== null) {
            $this->ensureAccountIsOnTheTeam($session, $data->userId);
        }

        $participant = new PlaytestParticipant;

        $participant->fill(['display_name' => $data->displayName]);

        $participant->session_id = $session->getKey();
        $participant->user_id = $data->userId;
        $participant->role = $data->role;

        /*
         * Somebody added to a session that is already running is arriving now.
         * Somebody added while it is still being planned has not arrived at
         * all, and stamping them with a join time would invent a fact.
         */
        $participant->joined_at = $data->joinedAt ?? ($session->isRunning() ? now()->toImmutable() : null);

        try {
            $participant->save();
        } catch (UniqueConstraintViolationException) {
            throw ParticipantIsAlreadyInSession::forUser((string) $data->userId);
        }

        $participant->setRelation('session', $session);

        event(new ParticipantAdded(
            participantId: $participant->id,
            sessionId: $session->getKey(),
            playtestId: $session->playtest_id,
            gameId: $session->playtest->game_id ?? '',
            userId: $participant->user_id,
            role: $participant->role,
            addedBy: $actor->id,
        ));

        return $participant;
    }

    /**
     * Require that a linked account shares the workspace the game lives in.
     */
    private function ensureAccountIsOnTheTeam(PlaytestSession $session, string $userId): void
    {
        $game = $session->playtest?->game;

        if ($game === null || ! $this->roster->isTeammate($game, $userId)) {
            throw ParticipantAccountIsNotOnTheTeam::forUser($userId);
        }
    }
}
