<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Models\GameEndCondition;
use Modules\Identity\Domain\Models\User;

/**
 * Remove an end condition.
 *
 * The condition that measured it survives. It is a named record in its own right
 * and may well be pointed at from somewhere else; deleting it here would break
 * whatever that was.
 */
final class DeleteGameEndCondition
{
    public function __construct(private readonly RuleWorkGuard $guard) {}

    public function handle(User $actor, GameEndCondition $outcome): void
    {
        $ruleSet = $outcome->ruleSet;

        if ($ruleSet !== null) {
            $this->guard->ensureRuleSetAcceptsChanges($ruleSet);
        }

        $outcome->delete();
    }
}
