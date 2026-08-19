<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Enums\IterationStatus;
use Modules\PrototypeIteration\Domain\Events\IterationStarted;
use Modules\PrototypeIteration\Domain\Exceptions\InvalidIterationTransition;
use Modules\PrototypeIteration\Domain\Models\Iteration;

/**
 * Begin the work on a planned iteration.
 *
 * Explicit rather than inferred, which is a deliberate departure from how a playtest
 * behaves. A playtest becomes in progress when its first session starts, because the
 * system can see that happen; a design cycle has no such signal — recording the first
 * change does not mean the cycle has begun, and plenty of iterations are planned in
 * detail weeks before anybody touches the prototype. So the designer says when, and
 * `started_at` records what they said rather than a guess.
 *
 * `started_at` is set from the server clock rather than accepted from the caller.
 * Every timestamp in this module that claims something happened is written by the
 * command that made it happen.
 *
 * The move is decided under a row lock and against the status read inside it, not
 * against whatever the caller was looking at. Two people pressing "Start" and
 * "Cancel" at the same moment therefore produce one winner and one honest refusal,
 * instead of a last-write-wins result where the losing action reports success.
 */
final class StartIteration
{
    public function __construct(private readonly DesignWorkGuard $guard) {}

    public function handle(User $actor, Iteration $iteration): Iteration
    {
        $this->guard->ensureIterationIsModifiable($iteration);

        $startedAt = now()->toImmutable();

        DB::transaction(function () use ($iteration, $startedAt): void {
            $fresh = Iteration::query()
                ->whereKey($iteration->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(IterationStatus::InProgress)) {
                throw InvalidIterationTransition::between($fresh->status, IterationStatus::InProgress);
            }

            $fresh->forceFill([
                'status' => IterationStatus::InProgress,
                'started_at' => $startedAt,
            ]);

            $fresh->save();

            /*
             * Carry the saved row back onto the instance the caller holds, so what
             * gets rendered afterwards is the state that was written rather than the
             * one that was read before the lock.
             */
            $iteration->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        event(new IterationStarted(
            iterationId: $iteration->id,
            gameId: $iteration->game_id,
            prototypeVersionId: $iteration->prototype_version_id,
            startedBy: $actor->id,
            startedAt: $startedAt->toDateTimeImmutable(),
        ));

        return $iteration;
    }
}
