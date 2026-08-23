<?php

namespace Modules\GameRules\Domain\Exceptions;

/**
 * Raised when something named in a request body belongs to a different rule set.
 *
 * The invariant the database cannot express, gathered into one exception because
 * it is one rule. A foreign key proves a phase, condition, trigger, rule or
 * action exists; only a lookup scoped by rule set proves it belongs to *this*
 * one. Section 53 of the brief lists the pairs; `RuleCatalogue` is where all of
 * them are checked.
 *
 * One class with named constructors rather than six near-identical ones, because
 * the six would differ only in a noun and would be six places for the wording to
 * drift. The `field` is what puts the refusal next to the picker the caller used
 * rather than in a toast.
 */
final class RecordDoesNotBelongToRuleSet extends RuleSystemViolation
{
    private function __construct(
        public readonly string $ruleSetId,
        public readonly string $recordId,
        private readonly string $attribute,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function forPhase(string $ruleSetId, string $phaseId, string $field = 'phase_id'): self
    {
        return new self($ruleSetId, $phaseId, $field, __('That phase belongs to a different rule set.'));
    }

    public static function forRule(string $ruleSetId, string $ruleId, string $field = 'rule_id'): self
    {
        return new self($ruleSetId, $ruleId, $field, __('That rule belongs to a different rule set.'));
    }

    public static function forAction(string $ruleSetId, string $actionId, string $field = 'action_id'): self
    {
        return new self($ruleSetId, $actionId, $field, __('That action belongs to a different rule set.'));
    }

    public static function forCondition(string $ruleSetId, string $conditionId, string $field = 'condition_id'): self
    {
        return new self($ruleSetId, $conditionId, $field, __('That condition belongs to a different rule set.'));
    }

    public static function forTrigger(string $ruleSetId, string $triggerId, string $field = 'trigger_id'): self
    {
        return new self($ruleSetId, $triggerId, $field, __('That trigger belongs to a different rule set.'));
    }

    public function field(): string
    {
        return $this->attribute;
    }
}
