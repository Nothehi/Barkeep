<?php

namespace Modules\GameRules\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameRules\Domain\Models\ConditionGroup;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * The groupings of those conditions, with their members loaded.
 */
final class GetConditionGroups
{
    public function __construct(private readonly RuleStructureRepository $structure) {}

    /**
     * @return Collection<int, ConditionGroup>
     */
    public function handle(RuleSet $ruleSet): Collection
    {
        return $this->structure->conditionGroupsOf($ruleSet);
    }
}
