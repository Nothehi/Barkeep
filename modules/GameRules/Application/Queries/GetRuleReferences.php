<?php

namespace Modules\GameRules\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameRules\Domain\Models\RuleReference;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * Every relationship between the rules in a set.
 *
 * Reached through the rules rather than through a column of its own, because
 * `rule_references` deliberately carries no `rule_set_id`.
 */
final class GetRuleReferences
{
    public function __construct(private readonly RuleStructureRepository $structure) {}

    /**
     * @return Collection<int, RuleReference>
     */
    public function handle(RuleSet $ruleSet): Collection
    {
        return $this->structure->referencesOf($ruleSet);
    }
}
