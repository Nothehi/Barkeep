<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Enums\RuleStatus;
use Modules\GameRules\Domain\Events\GameRuleDeleted;
use Modules\GameRules\Domain\Models\GameRule;
use Modules\Identity\Domain\Models\User;

/**
 * Remove a rule from a set.
 *
 * Its requirements, effects and the references pointing at it go with it — all of
 * those describe this rule and nothing else, so cascading is the correct reading
 * rather than a convenience.
 *
 * Its *children* do not. The foreign key nulls them, so deleting "Combat"
 * promotes "declare attacks", "choose defence", "resolve" and "apply damage" to
 * the top level rather than deleting four rules somebody wrote. That is the safe
 * reading of an ambiguous gesture: a designer removing a heading usually means
 * "these are not a group any more".
 *
 * Only a draft rule set reaches this at all. Retiring a rule from a system that is
 * in play is what {@see RuleStatus::Deprecated} is
 * for, in a clone.
 */
final class DeleteGameRule
{
    public function __construct(private readonly RuleWorkGuard $guard) {}

    public function handle(User $actor, GameRule $rule): void
    {
        $ruleSet = $rule->ruleSet;

        if ($ruleSet !== null) {
            $this->guard->ensureRuleSetAcceptsChanges($ruleSet);
        }

        $ruleId = $rule->getKey();
        $ruleSetId = $rule->rule_set_id;
        $slug = $rule->slug;

        $rule->delete();

        event(new GameRuleDeleted(
            ruleId: $ruleId,
            ruleSetId: $ruleSetId,
            slug: $slug,
        ));
    }
}
