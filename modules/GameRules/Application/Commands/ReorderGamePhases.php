<?php

namespace Modules\GameRules\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\Identity\Domain\Models\User;

/**
 * Put a rule set's phases into the order play visits them.
 *
 * The one reorder in this module that changes what the rules *say* rather than
 * how they are displayed. A turn structure read out of sequence is a different
 * turn structure, and the graph builder takes the first phase in this order as
 * where play begins when no phase claims to be setup.
 *
 * Every id is resolved through the rule set, so a list carrying somebody else's
 * phase renumbers nothing.
 */
final class ReorderGamePhases
{
    public function __construct(
        private readonly RuleCatalogue $catalogue,
        private readonly RuleWorkGuard $guard,
    ) {}

    /**
     * @param  list<string>  $orderedPhaseIds
     */
    public function handle(User $actor, RuleSet $ruleSet, array $orderedPhaseIds): void
    {
        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        $phases = [];

        foreach ($orderedPhaseIds as $phaseId) {
            $phases[] = $this->catalogue->phaseOf($ruleSet, $phaseId, 'phase_ids');
        }

        DB::transaction(function () use ($phases): void {
            foreach ($phases as $position => $phase) {
                $phase->position = $position;
                $phase->save();
            }
        });
    }
}
