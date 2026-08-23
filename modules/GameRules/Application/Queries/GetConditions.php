<?php

namespace Modules\GameRules\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameRules\Domain\Models\RuleCondition;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * The named logical requirements a rule set can point at.
 */
final class GetConditions
{
    public function __construct(private readonly RuleStructureRepository $structure) {}

    /**
     * @return Collection<int, RuleCondition>
     */
    public function handle(RuleSet $ruleSet): Collection
    {
        return $this->structure->conditionsOf($ruleSet);
    }
}
