<?php

namespace Modules\GameRules\Domain\Exceptions;

/**
 * Raised when a rule is pointed at itself, or at something that would close a
 * loop.
 *
 * References are the edges of the rule graph, and a cycle among the *directed*
 * ones — depends on, modifies, overrides, exception to — means neither rule can
 * be read first. "Related to" is symmetric and carries no order, so a mutual one
 * is a note rather than a contradiction and is left alone.
 */
final class InvalidRuleReference extends RuleSystemViolation
{
    private function __construct(
        public readonly string $ruleId,
        public readonly ?string $referencedRuleId,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function toItself(string $ruleId): self
    {
        return new self($ruleId, $ruleId, __('A rule cannot reference itself.'));
    }

    public static function wouldCycle(string $ruleId, string $referencedRuleId): self
    {
        return new self($ruleId, $referencedRuleId, __('That reference would create a loop between these rules.'));
    }

    public function field(): string
    {
        return 'referenced_rule_id';
    }
}
