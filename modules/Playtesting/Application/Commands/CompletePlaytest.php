<?php

namespace Modules\Playtesting\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Application\Services\PlaytestModificationGuard;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;
use Modules\Playtesting\Domain\Events\PlaytestCompleted;
use Modules\Playtesting\Domain\Exceptions\InvalidPlaytestTransition;
use Modules\Playtesting\Domain\Exceptions\PlaytestHasNoSessions;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Infrastructure\Persistence\Repositories\PlaytestRepository;

/**
 * Close a playtest as answered.
 *
 * Explicit, always. A playtest does not complete itself when its last session
 * ends, because "we have run three sessions" and "I have learned what I set
 * out to learn" are different claims and only the designer can make the
 * second. Plenty of investigations need a fourth group; plenty are settled
 * after one.
 *
 * The one rule enforced is that something happened. A playtest with no
 * sessions did not investigate anything, whatever was concluded, and closing
 * it as completed would put a fiction into the record. That playtest gets
 * cancelled instead, which says the same thing honestly.
 *
 * Note what is *not* required: that the sessions themselves completed. A
 * playtest whose every session was abandoned still taught the designer
 * something — usually that the version was not ready — and they are better
 * placed than this command to judge whether the question has been answered.
 *
 * The move is decided under a row lock and against the status read inside it,
 * not against whatever the caller was looking at. Two people pressing
 * "Complete" and "Cancel" at the same moment therefore produce one winner and
 * one honest refusal, instead of a last-write-wins result where the losing
 * action reports success.
 */
final class CompletePlaytest
{
    public function __construct(
        private readonly PlaytestModificationGuard $guard,
        private readonly PlaytestRepository $playtests,
    ) {}

    public function handle(User $actor, Playtest $playtest, ?string $conclusion = null): Playtest
    {
        $this->guard->ensurePlaytestIsModifiable($playtest);

        if (! $this->playtests->hasSessions($playtest)) {
            throw PlaytestHasNoSessions::forPlaytest($playtest->getKey());
        }

        $completedAt = now()->toImmutable();

        DB::transaction(function () use ($playtest, $conclusion, $completedAt): void {
            $fresh = Playtest::query()
                ->whereKey($playtest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(PlaytestStatus::Completed)) {
                throw InvalidPlaytestTransition::between($fresh->status, PlaytestStatus::Completed);
            }

            $fresh->forceFill([
                'status' => PlaytestStatus::Completed,
                'completed_at' => $completedAt,
            ]);

            if ($conclusion !== null) {
                $fresh->conclusion = $conclusion;
            }

            $fresh->save();

            /*
             * Carry the saved row back onto the instance the caller holds, so
             * what gets rendered afterwards is the state that was written
             * rather than the one that was read before the lock.
             */
            $playtest->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        event(new PlaytestCompleted(
            playtestId: $playtest->id,
            gameId: $playtest->game_id,
            gameVersionId: $playtest->game_version_id,
            completedBy: $actor->id,
            completedAt: $completedAt->toDateTimeImmutable(),
            sessionCount: $this->playtests->countSessionsOf($playtest),
        ));

        return $playtest;
    }
}
