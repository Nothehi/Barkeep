<?php

namespace Modules\GameRules\Application\Commands;

use Modules\GameRules\Application\Services\RuleWorkGuard;
use Modules\GameRules\Domain\Events\RuleReferenceDeleted;
use Modules\GameRules\Domain\Models\RuleReference;
use Modules\Identity\Domain\Models\User;

/**
 * Withdraw a claim that two rules are connected.
 *
 * Both rules survive. Only the edge goes.
 */
final class DeleteRuleReference
{
    public function __construct(private readonly RuleWorkGuard $guard) {}

    public function handle(User $actor, RuleReference $reference): void
    {
        $ruleSet = $reference->rule?->ruleSet;

        if ($ruleSet !== null) {
            $this->guard->ensureRuleSetAcceptsChanges($ruleSet);
        }

        $referenceId = $reference->getKey();
        $ruleId = $reference->rule_id;
        $referencedRuleId = $reference->referenced_rule_id;

        $reference->delete();

        event(new RuleReferenceDeleted(
            referenceId: $referenceId,
            ruleId: $ruleId,
            referencedRuleId: $referencedRuleId,
        ));
    }
}
