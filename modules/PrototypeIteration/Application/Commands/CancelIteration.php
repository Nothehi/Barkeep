<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Enums\IterationStatus;
use Modules\PrototypeIteration\Domain\Events\IterationCancelled;
use Modules\PrototypeIteration\Domain\Exceptions\InvalidIterationTransition;
use Modules\PrototypeIteration\Domain\Models\Iteration;

/**
 * Call a design cycle off.
 *
 * Available from planned and from in progress, because both happen constantly: a cycle
 * planned in March that the studio never got to, and a cycle that started and was
 * overtaken by something more urgent.
 *
 * No outcome is required and none is recorded, which is the whole difference between
 * this and completion. A cancelled iteration did not fail — failing is a result, and a
 * result means somebody looked. It stopped. Recording "failed" for abandoned work
 * would make the outcome column a record of the studio's calendar rather than of its
 * findings, and anything later reading those outcomes would draw the wrong conclusion.
 *
 * Whatever the cycle did produce stays exactly where it is. The changes recorded before
 * it was abandoned are still what somebody changed, and the notes are still readable —
 * they are simply frozen along with everything else, because a cancelled cycle is over.
 *
 * The move is decided under a row lock and against the status read inside it, so
 * cancelling something that has just been completed is refused rather than silently
 * overwriting the outcome.
 */
final class CancelIteration
{
    public function __construct(private readonly DesignWorkGuard $guard) {}

    public function handle(User $actor, Iteration $iteration): Iteration
    {
        $this->guard->ensureIterationIsModifiable($iteration);

        $cancelledAt = now()->toImmutable();

        DB::transaction(function () use ($iteration): void {
            $fresh = Iteration::query()
                ->whereKey($iteration->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(IterationStatus::Cancelled)) {
                throw InvalidIterationTransition::between($fresh->status, IterationStatus::Cancelled);
            }

            $fresh->forceFill(['status' => IterationStatus::Cancelled]);
            $fresh->save();

            $iteration->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        event(new IterationCancelled(
            iterationId: $iteration->id,
            gameId: $iteration->game_id,
            cancelledBy: $actor->id,
            cancelledAt: $cancelledAt->toDateTimeImmutable(),
        ));

        return $iteration;
    }
}
