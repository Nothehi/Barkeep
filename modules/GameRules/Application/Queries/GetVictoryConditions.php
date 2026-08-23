<?php

namespace Modules\GameRules\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\Models\VictoryCondition;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * The ways this game can be won, in the order they are checked.
 */
final class GetVictoryConditions
{
    public function __construct(private readonly RuleStructureRepository $structure) {}

    /**
     * @return Collection<int, VictoryCondition>
     */
    public function handle(RuleSet $ruleSet): Collection
    {
        return $this->structure->victoryConditionsOf($ruleSet);
    }
}
