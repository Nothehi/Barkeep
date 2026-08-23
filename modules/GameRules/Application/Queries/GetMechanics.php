<?php

namespace Modules\GameRules\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameRules\Domain\Models\RuleMechanic;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * The mechanisms a rule set says it uses.
 */
final class GetMechanics
{
    public function __construct(private readonly RuleStructureRepository $structure) {}

    /**
     * @return Collection<int, RuleMechanic>
     */
    public function handle(RuleSet $ruleSet): Collection
    {
        return $this->structure->mechanicsOf($ruleSet);
    }
}
