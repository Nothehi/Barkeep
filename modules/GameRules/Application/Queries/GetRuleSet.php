<?php

namespace Modules\GameRules\Application\Queries;

use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleSetRepository;

/**
 * One of a design state's rule sets, by id.
 *
 * What the route binding calls. Scoped to the version, so a set belonging to
 * another design state fails to resolve rather than being caught later by a
 * policy — which is what lets a rule set id be an opaque uuid in a URL without
 * being a capability.
 */
final class GetRuleSet
{
    public function __construct(private readonly RuleSetRepository $ruleSets) {}

    public function handle(GameVersion $version, string $ruleSetId): ?RuleSet
    {
        return $this->ruleSets->findForVersion($version, $ruleSetId);
    }
}
