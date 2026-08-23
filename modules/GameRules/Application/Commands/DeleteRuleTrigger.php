<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\RuleTriggerDeleted;
use Modules\GameRules\Domain\Models\RuleTrigger;
use Modules\Identity\Domain\Models\User;

/**
 * Remove a trigger.
 *
 * Transitions that fired off it keep their own row with the reference cleared —
 * the edge is still a way play can advance, it just no longer says what makes it
 * happen.
 */
final class DeleteRuleTrigger
{
    public function __construct(private readonly RuleWorkGuard $guard) {}

    public function handle(User $actor, RuleTrigger $trigger): void
    {
        $ruleSet = $trigger->ruleSet;

        if ($ruleSet !== null) {
            $this->guard->ensureRuleSetAcceptsChanges($ruleSet);
        }

        $triggerId = $trigger->getKey();
        $ruleSetId = $trigger->rule_set_id;
        $name = $trigger->name;

        $trigger->delete();

        event(new RuleTriggerDeleted(
            triggerId: $triggerId,
            ruleSetId: $ruleSetId,
            name: $name,
        ));
    }
}
