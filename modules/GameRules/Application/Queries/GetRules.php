<?php

namespace Modules\GameRules\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * Every rule in a set, flat, in reading order.
 *
 * Flat rather than nested, and one query rather than one per level. Whoever needs
 * the tree assembles it from `parent_rule_id` — which keeps a cycle in the data
 * from making a relation recurse forever, and makes the whole rulebook one round
 * trip.
 */
final class GetRules
{
    public function __construct(private readonly RuleStructureRepository $structure) {}

    /**
     * @return Collection<int, GameRule>
     */
    public function handle(RuleSet $ruleSet): Collection
    {
        return $this->structure->rulesOf($ruleSet);
    }
}
