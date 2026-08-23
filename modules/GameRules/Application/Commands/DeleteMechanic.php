<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\RuleMechanicDeleted;
use Modules\GameRules\Domain\Models\RuleMechanic;
use Modules\Identity\Domain\Models\User;

/**
 * Stop claiming this rule system uses a mechanism.
 *
 * Nothing points at a mechanic, so nothing goes with it. That is the difference
 * between a mechanic and a phase: one is a label on the whole design, the other
 * is a place things hang off.
 */
final class DeleteMechanic
{
    public function __construct(private readonly RuleWorkGuard $guard) {}

    public function handle(User $actor, RuleMechanic $mechanic): void
    {
        $ruleSet = $mechanic->ruleSet;

        if ($ruleSet !== null) {
            $this->guard->ensureRuleSetAcceptsChanges($ruleSet);
        }

        $mechanicId = $mechanic->getKey();
        $ruleSetId = $mechanic->rule_set_id;
        $slug = $mechanic->slug;

        $mechanic->delete();

        event(new RuleMechanicDeleted(
            mechanicId: $mechanicId,
            ruleSetId: $ruleSetId,
            slug: $slug,
        ));
    }
}
