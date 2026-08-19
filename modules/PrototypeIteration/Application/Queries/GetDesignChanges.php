<?php

namespace Modules\PrototypeIteration\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\PrototypeIteration\Domain\Models\DesignChange;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\IterationRepository;

/**
 * What a cycle changed, in the order it was recorded.
 *
 * Forwards, because a list of changes is read as an account of what was done rather than
 * as a stack of the most recent — "we removed the reaction phase, then reduced the
 * resolution steps" only makes sense in order.
 *
 * @see IterationRepository::changesOf()
 */
final class GetDesignChanges
{
    public function __construct(private readonly IterationRepository $iterations) {}

    /**
     * @return Collection<int, DesignChange>
     */
    public function handle(Iteration $iteration): Collection
    {
        return $this->iterations->changesOf($iteration);
    }
}
