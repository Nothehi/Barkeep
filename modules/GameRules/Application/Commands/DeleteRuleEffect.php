<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\RuleEffectDeleted;
use Modules\GameRules\Domain\Models\RuleEffect;
use Modules\Identity\Domain\Models\User;

/**
 * Remove one of the things a rule or an action does.
 */
final class DeleteRuleEffect
{
    public function __construct(private readonly RuleWorkGuard $guard) {}

    public function handle(User $actor, RuleEffect $effect): void
    {
        $ruleSet = $effect->ruleSet;

        if ($ruleSet !== null) {
            $this->guard->ensureRuleSetAcceptsChanges($ruleSet);
        }

        $effectId = $effect->getKey();
        $ruleSetId = $effect->rule_set_id;
        $target = $effect->target;

        $effect->delete();

        event(new RuleEffectDeleted(
            effectId: $effectId,
            ruleSetId: $ruleSetId,
            target: $target,
        ));
    }
}
