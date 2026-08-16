<?php

namespace Modules\Playtesting\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Application\DTOs\CompleteSessionData;
use Modules\Playtesting\Application\Services\PlaytestModificationGuard;
use Modules\Playtesting\Domain\Enums\PlaytestSessionStatus;
use Modules\Playtesting\Domain\Events\PlaytestSessionCompleted;
use Modules\Playtesting\Domain\Exceptions\InvalidSessionTransition;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Infrastructure\Persistence\Repositories\PlaytestRepository;

/**
 * End a session and close its record.
 *
 * Only a running session can be completed. "Completed" asserts that the game
 * was actually played, and the timestamps that make a session useful as
 * evidence only exist because somebody started it — so a session that was
 * never started is cancelled instead.
 *
 * Completing is the last thing done to a session. Afterwards it accepts no
 * more participants, observations or feedback, which is what makes everything
 * in it datable: it was all recorded while the session was open. That does
 * mean the outcome has to be written now or not at all, and the field is
 * optional precisely because of it — ending a session happens while people are
 * standing up and putting the box away, and a dialog that demands a write-up
 * first is a dialog that gets dismissed.
 *
 * The tally is gathered before the event is dispatched so that consumers get
 * what the session produced without having to read this module's tables.
 */
final class CompletePlaytestSession
{
    public function __construct(
        private readonly PlaytestModificationGuard $guard,
        private readonly PlaytestRepository $playtests,
    ) {}

    public function handle(User $actor, PlaytestSession $session, ?CompleteSessionData $data = null): PlaytestSession
    {
        $data ??= new CompleteSessionData;

        $this->guard->ensureSessionIsModifiable($session);

        $endedAt = now()->toImmutable();

        DB::transaction(function () use ($session, $data, $endedAt): void {
            $fresh = PlaytestSession::query()
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(PlaytestSessionStatus::Completed)) {
                throw InvalidSessionTransition::between($fresh->status, PlaytestSessionStatus::Completed);
            }

            $fresh->forceFill([
                'status' => PlaytestSessionStatus::Completed,
                'ended_at' => $endedAt,
            ]);

            if ($data->outcome !== null) {
                $fresh->outcome = $data->outcome;
            }

            /*
             * Notes are only overwritten when the caller sent some. A designer
             * who has been typing notes throughout the session leaves the
             * field alone in the closing dialog, and what they wrote survives.
             */
            if ($data->notes !== null) {
                $fresh->notes = $data->notes;
            }

            $fresh->save();

            $session->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        $tally = $this->playtests->tallyOf($session);
        $playtest = $session->playtest;

        event(new PlaytestSessionCompleted(
            sessionId: $session->id,
            playtestId: $session->playtest_id,
            gameId: $playtest->game_id ?? '',
            gameVersionId: $playtest->game_version_id ?? '',
            completedBy: $actor->id,
            endedAt: $endedAt->toDateTimeImmutable(),
            durationSeconds: $session->duration()?->seconds,
            participantCount: $tally['participants'],
            observationCount: $tally['observations'],
            feedbackCount: $tally['feedback'],
        ));

        return $session;
    }
}
