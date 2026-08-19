<?php

namespace Modules\PrototypeIteration\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\IterationRepository;

/**
 * What a cycle concluded, in the order it was proposed.
 *
 * Loaded with its citations, because a decision is read together with what supports it. A
 * decision list that made somebody click through to find out whether anything backed each
 * one would not get read, and the whole argument for recording evidence is that it appears
 * beside the conclusion it justifies.
 *
 * The citations arrive as rows; turning them into readable exhibits — the observation's
 * actual words — is `GetDecisionEvidence`'s job, because that needs Playtesting.
 *
 * @see IterationRepository::decisionsOf()
 */
final class GetDecisions
{
    public function __construct(private readonly IterationRepository $iterations) {}

    /**
     * @return Collection<int, DesignDecision>
     */
    public function handle(Iteration $iteration): Collection
    {
        return $this->iterations->decisionsOf($iteration);
    }
}
