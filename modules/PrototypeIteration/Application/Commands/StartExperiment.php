<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Enums\ExperimentStatus;
use Modules\PrototypeIteration\Domain\Events\ExperimentStarted;
use Modules\PrototypeIteration\Domain\Exceptions\InvalidExperimentTransition;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;

/**
 * Put an experiment into the field.
 *
 * The moment that closes the window on inventing a prediction. Everything written before
 * this point was written without knowing the answer, which is what makes the before half
 * of an experiment worth reading — so `started_at` is set from the server clock rather
 * than accepted from the caller.
 *
 * Decided under a row lock against the status read inside it, so an experiment cannot be
 * started twice or started after somebody cancelled it.
 */
final class StartExperiment
{
    public function __construct(private readonly DesignWorkGuard $guard) {}

    public function handle(User $actor, DesignExperiment $experiment): DesignExperiment
    {
        $this->guard->ensureExperimentIsModifiable($experiment);

        $startedAt = now()->toImmutable();

        DB::transaction(function () use ($experiment, $startedAt): void {
            $fresh = DesignExperiment::query()
                ->whereKey($experiment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(ExperimentStatus::Running)) {
                throw InvalidExperimentTransition::between($fresh->status, ExperimentStatus::Running);
            }

            $fresh->forceFill([
                'status' => ExperimentStatus::Running,
                'started_at' => $startedAt,
            ]);

            $fresh->save();

            $experiment->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        event(new ExperimentStarted(
            experimentId: $experiment->id,
            iterationId: $experiment->iteration_id,
            gameId: $experiment->gameId(),
            startedBy: $actor->id,
            startedAt: $startedAt->toDateTimeImmutable(),
        ));

        return $experiment;
    }
}
