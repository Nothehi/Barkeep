<?php

namespace Modules\GameRules\Application\Queries;

use Modules\GameRules\Domain\Models\GameRule;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * One of a set's rules, by id.
 *
 * Scoped to the rule set, which is what the route binding relies on: a rule id
 * from another rule system fails to resolve rather than 403ing.
 */
final class GetRule
{
    public function __construct(private readonly RuleStructureRepository $structure) {}

    public function handle(RuleSet $ruleSet, string $ruleId): ?GameRule
    {
        return $this->structure->findRuleInRuleSet($ruleSet, $ruleId);
    }
}
