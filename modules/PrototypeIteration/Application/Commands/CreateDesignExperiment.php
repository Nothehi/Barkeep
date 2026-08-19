<?php

namespace Modules\PrototypeIteration\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\PrototypeIteration\Application\DTOs\DesignExperimentData;
use Modules\PrototypeIteration\Application\Services\DesignWorkGuard;
use Modules\PrototypeIteration\Domain\Enums\ExperimentStatus;
use Modules\PrototypeIteration\Domain\Events\ExperimentCreated;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;

/**
 * Write down a question the studio intends to answer.
 *
 * This command can only write the *before* half of an experiment — the question, the
 * hypothesis, the method and what the designer expects. There is no path through it that
 * sets a result, and that restriction is the entire point: an experiment whose prediction
 * and outcome could be written in one request would be a record of a prediction that was
 * never at risk.
 *
 * Only the question is required. Exploratory work is real work, and demanding a
 * hypothesis for "let us run it and watch" would produce invented predictions — which is
 * worse than none, because an invented prediction that happens to come true reads as
 * insight.
 *
 * The experiment starts planned. Running it and answering it are separate acts with their
 * own commands, so the record can distinguish "we meant to test this" from "we tested
 * it" from "we found out".
 */
final class CreateDesignExperiment
{
    public function __construct(private readonly DesignWorkGuard $guard) {}

    public function handle(User $creator, Iteration $iteration, DesignExperimentData $data): DesignExperiment
    {
        $this->guard->ensureIterationAcceptsWork($iteration);

        $experiment = new DesignExperiment;

        $experiment->fill([
            'title' => $data->title,
            'question' => $data->question,
            'hypothesis' => $data->hypothesis,
            'method' => $data->method,
            'expected_result' => $data->expectedResult,
        ]);

        $experiment->iteration_id = $iteration->getKey();
        $experiment->status = ExperimentStatus::default();
        $experiment->created_by = $creator->id;

        $experiment->save();

        $experiment->setRelation('iteration', $iteration);
        $experiment->setRelation('creator', $creator);

        event(new ExperimentCreated(
            experimentId: $experiment->id,
            iterationId: $iteration->getKey(),
            gameId: $iteration->game_id,
            createdBy: $creator->id,
        ));

        return $experiment;
    }
}
