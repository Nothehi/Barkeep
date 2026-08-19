<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\DTOs\CompleteIterationData;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Enums\IterationStatus;
use Modules\PrototypeIteration\Domain\Events\IterationCompleted;
use Modules\PrototypeIteration\Domain\Exceptions\InvalidIterationTransition;
use Modules\PrototypeIteration\Domain\Exceptions\IterationNeedsAnOutcome;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\IterationRepository;

/**
 * Close a turn of the design loop.
 *
 * The most consequential command in the module. Completing an iteration turns it from
 * work in progress into design history: its plan, its changes and its decisions all
 * stop being editable at this moment, because everything that comes next is built on
 * what this cycle concluded.
 *
 * ## What is required
 *
 * An outcome and a summary, both. That is section 47's rule and it is enforced here as
 * well as in the form request, because a form request only guards the HTTP door. An
 * iteration with no outcome is a period of time; the outcome is the index and the
 * summary is the account, and a history missing either is one nobody can read back.
 *
 * The transition is also stricter than a playtest's: an iteration cannot go straight
 * from planned to completed. A cycle that closed without ever starting would carry an
 * outcome nobody gathered evidence for, and the honest ending for work that never
 * happened is cancellation.
 *
 * ## What is deliberately *not* done
 *
 * **Experiments are not completed.** An experiment still running when the cycle closes
 * stayed unanswered, and marking it complete would put a result into the record that
 * nobody observed. Section 22 says so explicitly, and the summary counts completed
 * experiments separately precisely so the gap stays visible.
 *
 * **No game version is created.** Whether a cycle's conclusions amount to a new design
 * state is a judgement, and taking it from the designer would fill a game's history
 * with versions nobody meant. Section 30's loop ends with a person deciding — see
 * `CreateNextGameVersion`, which is a separate command behind a separate action.
 *
 * **Nothing is scored.** The counts on the event are facts; "this iteration went well"
 * is not something the platform is in a position to say.
 *
 * The move is decided under a row lock and against the status read inside it, so two
 * people closing and cancelling at the same moment produce one winner and one honest
 * refusal rather than a last-write-wins result.
 */
final class CompleteIteration
{
    public function __construct(
        private readonly DesignWorkGuard $guard,
        private readonly IterationRepository $iterations,
    ) {}

    public function handle(User $actor, Iteration $iteration, CompleteIterationData $data): Iteration
    {
        $this->guard->ensureIterationIsModifiable($iteration);

        if (trim($data->summary) === '') {
            throw IterationNeedsAnOutcome::forIteration($iteration->getKey());
        }

        $completedAt = now()->toImmutable();

        DB::transaction(function () use ($iteration, $data, $completedAt): void {
            $fresh = Iteration::query()
                ->whereKey($iteration->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(IterationStatus::Completed)) {
                throw InvalidIterationTransition::between($fresh->status, IterationStatus::Completed);
            }

            $fresh->forceFill([
                'status' => IterationStatus::Completed,
                'outcome' => $data->outcome,
                'summary' => $data->summary,
                'completed_at' => $completedAt,
            ]);

            $fresh->save();

            $iteration->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        /*
         * Counted after the write rather than before, so the event describes the
         * cycle as it was closed. The figures travel with the event because "closed
         * after four changes, two experiments and three playtests" and "closed after
         * one change and no evidence" are different events to anything reasoning
         * about design work — and making a consumer count them would mean every
         * consumer reaching into this module's tables.
         */
        $tally = $this->iterations->tally($iteration);

        event(new IterationCompleted(
            iterationId: $iteration->id,
            gameId: $iteration->game_id,
            gameVersionId: $iteration->game_version_id,
            prototypeVersionId: $iteration->prototype_version_id,
            outcome: $data->outcome,
            completedBy: $actor->id,
            completedAt: $completedAt->toDateTimeImmutable(),
            changeCount: $tally['changes'],
            experimentCount: $tally['experiments'],
            decisionCount: $tally['decisions'],
            playtestCount: $tally['playtests'],
        ));

        return $iteration;
    }
}
