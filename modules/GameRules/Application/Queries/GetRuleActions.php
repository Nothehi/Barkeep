<?php

namespace Modules\GameRules\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\GameRules\Infrastructure\Persistence\Repositories\RuleStructureRepository;

/**
 * The actions a rule set declares.
 *
 * Counts rather than contents: an actions list draws "2 requirements, 3 effects"
 * per row, and loading every line to render a number would be two queries per
 * action for a screen that needs two in total.
 */
final class GetRuleActions
{
    public function __construct(private readonly RuleStructureRepository $structure) {}

    /**
     * @return Collection<int, RuleAction>
     */
    public function handle(RuleSet $ruleSet): Collection
    {
        return $this->structure->actionsOf($ruleSet);
    }
}
