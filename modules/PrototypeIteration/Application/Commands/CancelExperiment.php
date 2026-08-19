<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Enums\ExperimentStatus;
use Modules\PrototypeIteration\Domain\Events\ExperimentCancelled;
use Modules\PrototypeIteration\Domain\Exceptions\InvalidExperimentTransition;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;

/**
 * Abandon a question.
 *
 * The honest ending for an experiment that stopped mattering — the change it was testing
 * was reverted, the session never happened, the question answered itself. It exists as a
 * distinct ending from completion for one reason: a cancelled experiment produced no
 * result, and anything that counted it as one would be reading an answer into silence.
 *
 * This is also what a designer reaches for instead of completing an experiment with an
 * empty result, which the module refuses. Offering a way to say "we did not find out"
 * is what makes that refusal reasonable rather than obstructive.
 */
final class CancelExperiment
{
    public function __construct(private readonly DesignWorkGuard $guard) {}

    public function handle(User $actor, DesignExperiment $experiment): DesignExperiment
    {
        $this->guard->ensureExperimentIsModifiable($experiment);

        $cancelledAt = now()->toImmutable();

        DB::transaction(function () use ($experiment): void {
            $fresh = DesignExperiment::query()
                ->whereKey($experiment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(ExperimentStatus::Cancelled)) {
                throw InvalidExperimentTransition::between($fresh->status, ExperimentStatus::Cancelled);
            }

            $fresh->forceFill(['status' => ExperimentStatus::Cancelled]);
            $fresh->save();

            $experiment->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        event(new ExperimentCancelled(
            experimentId: $experiment->id,
            iterationId: $experiment->iteration_id,
            gameId: $experiment->gameId(),
            cancelledBy: $actor->id,
            cancelledAt: $cancelledAt->toDateTimeImmutable(),
        ));

        return $experiment;
    }
}
