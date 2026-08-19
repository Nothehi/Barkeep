<?php

namespace Modules\PrototypeIteration\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\IterationRepository;

/**
 * What a cycle tried out, in the order it was designed.
 *
 * Not filtered by status, deliberately. A cycle holding a planned experiment, a running one
 * and two completed ones is the normal case, and the screen needs all four — an experiment
 * left running when the iteration closed is exactly the thing a reader should see, since
 * this module refuses to complete one on the cycle's behalf.
 *
 * @see IterationRepository::experimentsOf()
 */
final class GetExperiments
{
    public function __construct(private readonly IterationRepository $iterations) {}

    /**
     * @return Collection<int, DesignExperiment>
     */
    public function handle(Iteration $iteration): Collection
    {
        return $this->iterations->experimentsOf($iteration);
    }
}
