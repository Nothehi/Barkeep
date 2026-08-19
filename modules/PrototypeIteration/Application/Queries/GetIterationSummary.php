<?php

namespace Modules\PrototypeIteration\Application\Queries;

use Modules\PrototypeIteration\Application\DTOs\IterationSummary;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\IterationRepository;
use Modules\PrototypeIteration\Infrastructure\Playtesting\PlaytestEvidence;

/**
 * Everything a design cycle has produced, counted on read.
 *
 * A query rather than an analytics subsystem, and that is the whole design decision. Nothing
 * is persisted, no rollup table exists and no listener maintains a counter — because the
 * moment a stored count and the rows it describes can disagree, somebody spends an afternoon
 * finding out which one is lying. Section 33 asks for exactly this.
 *
 * It is affordable because the numbers are small: a cycle has a handful of changes and a
 * couple of decisions. When a studio eventually wants figures across a whole project's
 * history, that is a reporting problem for the analytics capability rather than a reason to
 * denormalise this.
 *
 * ## The two halves
 *
 * The repository counts this module's own tables; the Playtesting adapter counts what the
 * attached playtests contain. Keeping the two apart is what lets the repository stay ignorant
 * of Playtesting — a repository that reached across the seam would be the first place the
 * boundary quietly stopped holding — and it is why the playtesting figures arrive as zero
 * from `summarise()` and are filled in here.
 *
 * The observation and feedback totals are therefore Playtesting's own numbers, at the moment
 * of the query, rather than a copy this module keeps. An iteration screen and a playtest
 * screen showing different counts for the same evidence is exactly the failure that arrangement
 * prevents.
 */
final class GetIterationSummary
{
    public function __construct(
        private readonly IterationRepository $iterations,
        private readonly PlaytestEvidence $playtesting,
    ) {}

    public function handle(Iteration $iteration): IterationSummary
    {
        $own = $this->iterations->summarise($iteration);
        $game = $iteration->game;

        if ($game === null) {
            return $own;
        }

        $evidence = $this->playtesting->tallyFor(
            $game,
            $this->iterations->playtestLinksOf($iteration),
        );

        return new IterationSummary(
            iteration: $iteration,
            changeCount: $own->changeCount,
            experimentCount: $own->experimentCount,
            completedExperimentCount: $own->completedExperimentCount,
            decisionCount: $own->decisionCount,
            acceptedDecisionCount: $own->acceptedDecisionCount,
            evidenceCount: $own->evidenceCount,
            playtestCount: $evidence['playtests'],
            sessionCount: $evidence['sessions'],
            observationCount: $evidence['observations'],
            feedbackCount: $evidence['feedback'],
        );
    }
}
