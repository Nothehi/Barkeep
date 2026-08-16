<?php

namespace Modules\Playtesting\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Application\Services\PlaytestModificationGuard;
use Modules\Playtesting\Domain\Enums\PlaytestSessionStatus;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;
use Modules\Playtesting\Domain\Events\PlaytestSessionStarted;
use Modules\Playtesting\Domain\Exceptions\InvalidSessionTransition;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestSession;

/**
 * Begin a session: people have sat down and the game is starting.
 *
 * The timestamp comes from the clock rather than from the request, because it
 * is the anchor everything else in the session hangs off — the duration, the
 * order of the timeline, the elapsed counter on the screen. A caller-supplied
 * start time would let any of those be quietly wrong.
 *
 * ## Two people pressing start at once
 *
 * A facilitator and a designer both reaching for the button is the normal case
 * rather than a hypothetical, since both are looking at the same session on
 * their own devices. Deciding the move under a row lock, against the status
 * read inside it, means one of them starts the session and the other is told
 * it is already running — rather than the second press overwriting the first
 * one's `started_at` and shortening the session by however long they took.
 *
 * ## Why the playtest moves too
 *
 * Starting the first session of a playtest puts the playtest itself in
 * progress. This is the one place in the module where acting on a session
 * changes its parent, and it earns that: an investigation whose first sitting
 * has begun *is* under way, and making a designer say so separately would be
 * asking them to maintain a status the system can see for itself.
 *
 * Completion is not automatic in the same way, and the asymmetry is deliberate
 * — see {@see CompletePlaytest} for why finishing is a judgement rather than
 * an observation.
 */
final class StartPlaytestSession
{
    public function __construct(private readonly PlaytestModificationGuard $guard) {}

    public function handle(User $actor, PlaytestSession $session): PlaytestSession
    {
        $this->guard->ensureSessionIsModifiable($session);

        $startedAt = now()->toImmutable();

        DB::transaction(function () use ($session, $startedAt): void {
            $fresh = PlaytestSession::query()
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(PlaytestSessionStatus::InProgress)) {
                throw InvalidSessionTransition::between($fresh->status, PlaytestSessionStatus::InProgress);
            }

            $fresh->forceFill([
                'status' => PlaytestSessionStatus::InProgress,
                'started_at' => $startedAt,
            ])->save();

            $session->setRawAttributes($fresh->getAttributes(), sync: true);

            $this->promotePlaytest($session);
        });

        event(new PlaytestSessionStarted(
            sessionId: $session->id,
            playtestId: $session->playtest_id,
            gameId: $session->playtest->game_id ?? '',
            startedBy: $actor->id,
            startedAt: $startedAt->toDateTimeImmutable(),
        ));

        return $session;
    }

    /**
     * Move the playtest to in progress if it has not moved already.
     *
     * Done inside the same transaction and under its own lock, so the second
     * session to start does not race the first into writing the same status.
     * A playtest that is already in progress is left alone rather than
     * rewritten, which keeps this idempotent across every later session.
     */
    private function promotePlaytest(PlaytestSession $session): void
    {
        $playtest = Playtest::query()
            ->whereKey($session->playtest_id)
            ->lockForUpdate()
            ->first();

        if ($playtest === null || $playtest->status !== PlaytestStatus::Planned) {
            return;
        }

        $playtest->forceFill(['status' => PlaytestStatus::InProgress])->save();

        $loaded = $session->playtest;

        if ($loaded !== null) {
            $loaded->setRawAttributes($playtest->getAttributes(), sync: true);
        }
    }
}
