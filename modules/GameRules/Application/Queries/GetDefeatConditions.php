<?php

namespace Modules\GameRules\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameRules\Domain\Models\DefeatCondition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * The ways a player can be knocked out, in the order they are checked.
 */
final class GetDefeatConditions
{
    public function __construct(private readonly RuleStructureRepository $structure) {}

    /**
     * @return Collection<int, DefeatCondition>
     */
    public function handle(RuleSet $ruleSet): Collection
    {
        return $this->structure->defeatConditionsOf($ruleSet);
    }
}
