<?php

namespace Modules\GameRules\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameRules\Domain\Models\GamePhase;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * The stages of play, in the order play visits them.
 *
 * That order is a rule rather than a preference: a turn structure read out of
 * sequence is a different turn structure.
 */
final class GetGamePhases
{
    public function __construct(private readonly RuleStructureRepository $structure) {}

    /**
     * @return Collection<int, GamePhase>
     */
    public function handle(RuleSet $ruleSet): Collection
    {
        return $this->structure->phasesOf($ruleSet);
    }
}
