<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\RuleActionDeleted;
use Modules\GameRules\Domain\Models\RuleAction;
use Modules\Identity\Domain\Models\User;

/**
 * Remove something a player could do.
 *
 * Its requirements and effects go with it — they describe this action and nothing
 * else, so cascading is the correct reading rather than a convenience.
 *
 * Nothing in GameEconomy is touched. The economy action this one pointed at is a
 * different record in a different bounded context, and it goes on existing: a
 * studio removing a rule action has said nothing about whether the costs it named
 * are still part of their model.
 */
final class DeleteRuleAction
{
    public function __construct(private readonly RuleWorkGuard $guard) {}

    public function handle(User $actor, RuleAction $action): void
    {
        $ruleSet = $action->ruleSet;

        if ($ruleSet !== null) {
            $this->guard->ensureRuleSetAcceptsChanges($ruleSet);
        }

        $actionId = $action->getKey();
        $ruleSetId = $action->rule_set_id;
        $slug = $action->slug;

        $action->delete();

        event(new RuleActionDeleted(
            actionId: $actionId,
            ruleSetId: $ruleSetId,
            slug: $slug,
        ));
    }
}
