<?php

namespace Modules\Playtesting\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Application\Services\PlaytestModificationGuard;
use Modules\Playtesting\Domain\Enums\PlaytestSessionStatus;
use Modules\Playtesting\Domain\Events\PlaytestSessionCancelled;
use Modules\Playtesting\Domain\Exceptions\InvalidSessionTransition;
use Modules\Playtesting\Domain\Models\PlaytestSession;

/**
 * Call a session off, before it started or part way through.
 *
 * Both are the same decision made at different moments, so both land here. A
 * session cancelled the day before never has a `started_at`; one abandoned
 * after two rounds does, and the pair of timestamps is what tells the two
 * apart later without needing separate statuses for them.
 *
 * A completed session cannot be cancelled. Allowing it would mean a finished
 * sitting could be reclassified and quietly dropped out of the evidence, which
 * is the one thing a record of what was played must not permit.
 *
 * Whatever was recorded before the cancellation stays. Four observations from
 * an abandoned session are four things somebody noticed, and the reason the
 * session was abandoned is usually among them.
 */
final class CancelPlaytestSession
{
    public function __construct(private readonly PlaytestModificationGuard $guard) {}

    public function handle(User $actor, PlaytestSession $session): PlaytestSession
    {
        $this->guard->ensureSessionIsModifiable($session);

        $cancelledAt = now()->toImmutable();

        DB::transaction(function () use ($session): void {
            $fresh = PlaytestSession::query()
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(PlaytestSessionStatus::Cancelled)) {
                throw InvalidSessionTransition::between($fresh->status, PlaytestSessionStatus::Cancelled);
            }

            $fresh->forceFill(['status' => PlaytestSessionStatus::Cancelled])->save();

            $session->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        event(new PlaytestSessionCancelled(
            sessionId: $session->id,
            playtestId: $session->playtest_id,
            gameId: $session->playtest->game_id ?? '',
            cancelledBy: $actor->id,
            cancelledAt: $cancelledAt->toDateTimeImmutable(),
        ));

        return $session;
    }
}
