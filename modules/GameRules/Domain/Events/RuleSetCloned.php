<?php

namespace Modules\GameRules\Domain\Events;

/**
 * Dispatched when a rule set is copied into a fresh draft.
 *
 * The module's answer to "I want to change the rules that are in play". Section
 * 55 of the brief refuses the edit and offers this instead, so this event is how
 * a rule system's history is actually made: clone, change, activate, repeat.
 *
 * The clone is a complete and independent copy — every rule, phase, transition,
 * action, requirement, condition, group, effect, trigger, outcome and reference,
 * with new ids and the relationships between them preserved. Nothing in the new
 * draft shares a row with the original, which is what makes changing it safe.
 *
 * The counts travel with the event because "cloned 24 rules and 7 phases" is the
 * sentence a consumer wants, and recomputing it would mean loading both sets.
 */
final readonly class RuleSetCloned
{
    public function __construct(
        public string $ruleSetId,
        public string $sourceRuleSetId,
        public string $gameVersionId,
        public int $recordsCopied,
        public string $clonedBy,
    ) {}
}
