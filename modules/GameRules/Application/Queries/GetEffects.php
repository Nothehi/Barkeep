<?php

namespace Modules\GameRules\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameRules\Domain\Models\RuleEffect;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * Everything a rule set says happens, across its rules and actions.
 */
final class GetEffects
{
    public function __construct(private readonly RuleStructureRepository $structure) {}

    /**
     * @return Collection<int, RuleEffect>
     */
    public function handle(RuleSet $ruleSet): Collection
    {
        return $this->structure->effectsOf($ruleSet);
    }
}
