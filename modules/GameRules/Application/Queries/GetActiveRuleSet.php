<?php

namespace Modules\GameRules\Application\Queries;

use Modules\GameDesign\Domain\Models\GameVersion;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleSetRepository;

/**
 * The rule system currently in play for a design state.
 *
 * Null is an ordinary answer rather than an error: a version whose rules nobody
 * has activated yet has none, and that is most of them for most of their life.
 *
 * At most one row can match. The partial unique index makes that a fact about the
 * data rather than an assumption about the query.
 */
final class GetActiveRuleSet
{
    public function __construct(private readonly RuleSetRepository $ruleSets) {}

    public function handle(GameVersion $version): ?RuleSet
    {
        return $this->ruleSets->activeForVersion($version);
    }
}
