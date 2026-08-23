<?php

namespace Modules\GameRules\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameRules\Domain\Models\RuleRequirement;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * Every gate in a rule set, across its rules and actions.
 */
final class GetRequirements
{
    public function __construct(private readonly RuleStructureRepository $structure) {}

    /**
     * @return Collection<int, RuleRequirement>
     */
    public function handle(RuleSet $ruleSet): Collection
    {
        return $this->structure->requirementsOf($ruleSet);
    }
}
