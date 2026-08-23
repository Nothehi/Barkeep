<?php

namespace Modules\GameRules\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Domain\Models\RuleTrigger;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * The things a rule set says happen automatically.
 *
 * Recorded, never fired — see `RuleTrigger`.
 */
final class GetTriggers
{
    public function __construct(private readonly RuleStructureRepository $structure) {}

    /**
     * @return Collection<int, RuleTrigger>
     */
    public function handle(RuleSet $ruleSet): Collection
    {
        return $this->structure->triggersOf($ruleSet);
    }
}
