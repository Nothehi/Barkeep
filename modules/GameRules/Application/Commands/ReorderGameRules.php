<?php

namespace Modules\GameRules\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\GameRules\Application\Services\RuleCatalogue;
use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Models\RuleSet;
use Modules\Identity\Domain\Models\User;

/**
 * Put a rule set's rules into the order the designer dragged them into.
 *
 * Takes the whole ordered list rather than one rule and a new index, which is the
 * shape a drag-and-drop actually produces and the only shape that cannot go
 * half-wrong. Moving one rule "to position 3" leaves every other position to be
 * inferred, and two people reordering at once would infer differently.
 *
 * Every id is resolved through the rule set before anything is written, so a list
 * carrying somebody else's rule renumbers nothing. Ids the list omits keep the
 * positions they had: a client that sends only the rules under one parent is
 * reordering that branch, which is exactly what the rule tree does.
 *
 * One transaction, because a half-applied ordering is a list nobody can read.
 */
final class ReorderGameRules
{
    public function __construct(
        private readonly RuleCatalogue $catalogue,
        private readonly RuleWorkGuard $guard,
    ) {}

    /**
     * @param  list<string>  $orderedRuleIds
     */
    public function handle(User $actor, RuleSet $ruleSet, array $orderedRuleIds): void
    {
        $this->guard->ensureRuleSetAcceptsChanges($ruleSet);

        $rules = [];

        foreach ($orderedRuleIds as $ruleId) {
            $rules[] = $this->catalogue->ruleOf($ruleSet, $ruleId, 'rule_ids');
        }

        DB::transaction(function () use ($rules): void {
            foreach ($rules as $position => $rule) {
                $rule->position = $position;
                $rule->save();
            }
        });
    }
}
