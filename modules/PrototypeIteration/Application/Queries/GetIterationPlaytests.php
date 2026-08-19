<?php

namespace Modules\PrototypeIteration\Application\Queries;

use Illuminate\Support\Collection;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Domain\ValueObjects\PlaytestReference;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\IterationRepository;
use Modules\PrototypeIteration\Infrastructure\Playtesting\PlaytestEvidence;

/**
 * The playtests a cycle was tested through, with enough about each to recognise it.
 *
 * Two steps that are kept apart on purpose: the repository reads the join rows, and the
 * Playtesting adapter turns them into references. Nothing in between ever holds a Playtest.
 *
 * The counts on each reference — sessions, participants, observations, feedback — are read
 * from Playtesting at the moment of the query rather than cached on the link. That costs a
 * few aggregates per attached playtest and buys the property that matters: the numbers an
 * iteration screen shows are the numbers the playtest's own screen shows. A count stored on
 * the join row would start disagreeing the first time somebody added a session, and would
 * then disagree forever, in a place nobody thinks to look.
 *
 * A link whose playtest cannot be read comes back as unavailable rather than being dropped.
 * "This iteration cited evidence you cannot see" is true and useful; a silently shorter list
 * reads as "this iteration cited nothing".
 */
final class GetIterationPlaytests
{
    public function __construct(
        private readonly IterationRepository $iterations,
        private readonly PlaytestEvidence $playtesting,
    ) {}

    /**
     * @return Collection<int, PlaytestReference>
     */
    public function handle(Iteration $iteration): Collection
    {
        $game = $iteration->game;
        $links = $this->iterations->playtestLinksOf($iteration);

        if ($game === null) {
            return Collection::make();
        }

        return $this->playtesting->referencesFor($game, $links);
    }
}
