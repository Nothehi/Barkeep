<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\DTOs\CompleteExperimentData;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Enums\ExperimentStatus;
use Modules\PrototypeIteration\Domain\Events\ExperimentCompleted;
use Modules\PrototypeIteration\Domain\Exceptions\ExperimentNeedsAResult;
use Modules\PrototypeIteration\Domain\Exceptions\InvalidExperimentTransition;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;

/**
 * Record what an experiment actually produced.
 *
 * The only command that may write the *after* half of an experiment, which is what makes
 * the before half trustworthy: the result arrives through a different door from the
 * prediction, after the experiment has been run.
 *
 * The result is required. An experiment completed with nothing observed would put a
 * settled-looking entry into the iteration timeline that answers nothing, and the
 * timeline is read as an account of what the studio found out. A question that stopped
 * mattering gets cancelled instead, which says so honestly.
 *
 * The conclusion is not required, and that gap is real rather than lenient. "Sessions ran
 * twenty minutes longer" is something the person at the table already knows; "unlimited
 * actions improve strategy but harm pacing" is an argument that usually arrives days
 * later, after somebody has read the observations back. Demanding both in one request
 * would produce conclusions written to fill a field, and `ExperimentCompleted` carries
 * whether one was drawn precisely so the difference stays visible.
 *
 * Nothing about this command touches the iteration around it. Completing the last
 * experiment does not complete the cycle, because whether the question the *cycle* asked
 * has been answered is a judgement only the designer can make.
 */
final class CompleteExperiment
{
    public function __construct(private readonly DesignWorkGuard $guard) {}

    public function handle(User $actor, DesignExperiment $experiment, CompleteExperimentData $data): DesignExperiment
    {
        $this->guard->ensureExperimentIsModifiable($experiment);

        if (trim($data->actualResult) === '') {
            throw ExperimentNeedsAResult::forExperiment($experiment->getKey());
        }

        $completedAt = now()->toImmutable();

        DB::transaction(function () use ($experiment, $data, $completedAt): void {
            $fresh = DesignExperiment::query()
                ->whereKey($experiment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(ExperimentStatus::Completed)) {
                throw InvalidExperimentTransition::between($fresh->status, ExperimentStatus::Completed);
            }

            $fresh->forceFill([
                'status' => ExperimentStatus::Completed,
                'actual_result' => $data->actualResult,
                'conclusion' => $data->conclusion,
                'completed_at' => $completedAt,
            ]);

            $fresh->save();

            $experiment->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        event(new ExperimentCompleted(
            experimentId: $experiment->id,
            iterationId: $experiment->iteration_id,
            gameId: $experiment->gameId(),
            hasConclusion: $data->conclusion !== null,
            completedBy: $actor->id,
            completedAt: $completedAt->toDateTimeImmutable(),
        ));

        return $experiment;
    }
}
