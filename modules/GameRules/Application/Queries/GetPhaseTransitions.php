<?php

namespace Modules\GameRules\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameRules\Domain\Models\PhaseTransition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * Every way play can move between phases, with both ends and its guard loaded.
 *
 * One query with eager loads rather than one per edge: the graph and the phase
 * designer both read the whole thing at once.
 */
final class GetPhaseTransitions
{
    public function __construct(private readonly RuleStructureRepository $structure) {}

    /**
     * @return Collection<int, PhaseTransition>
     */
    public function handle(RuleSet $ruleSet): Collection
    {
        return $this->structure->transitionsOf($ruleSet);
    }
}
