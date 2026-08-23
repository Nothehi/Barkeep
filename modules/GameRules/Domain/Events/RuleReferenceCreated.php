<?php

namespace Modules\GameRules\Domain\Events;

use Modules\GameRules\Domain\Enums\ReferenceType;

/**
 * Dispatched when a designer records how one rule relates to another.
 *
 * An edge in the rule graph. Both rules always belong to the same set, which is
 * proved before the write rather than assumed by a consumer.
 *
 * Requirements and condition-group memberships deliberately have no events of
 * their own: both are details *of* the record they hang on, and a consumer that
 * cares about an action's requirements is already listening for the action. A
 * rule reference is different — it is a fact about two rules and belongs to
 * neither.
 */
final readonly class RuleReferenceCreated
{
    public function __construct(
        public string $referenceId,
        public string $ruleId,
        public string $referencedRuleId,
        public ReferenceType $referenceType,
    ) {}
}
