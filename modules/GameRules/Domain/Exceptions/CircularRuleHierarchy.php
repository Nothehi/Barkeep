<?php

namespace Modules\GameRules\Domain\Exceptions;

/**
 * Raised when reparenting a rule or a phase would make it its own ancestor.
 *
 * Section 54 of the brief, refused at the point of writing rather than only
 * reported afterwards. The two hierarchies get one exception because they are
 * one problem: a tree with a loop in it has no top, so nothing can render it,
 * walk it or print it as a rulebook.
 *
 * The validator reports the same shape as an error, which is not redundant. This
 * stops a cycle being created through a command; the validator catches one that
 * predates the check — data restored from a backup, a clone of a set written
 * before it existed — and says so on the screen rather than at a moment nobody
 * is looking.
 *
 * Reported against `parent_rule_id` / `parent_phase_id` so the refusal lands on
 * the picker the caller just used.
 */
final class CircularRuleHierarchy extends RuleSystemViolation
{
    private function __construct(
        public readonly string $recordId,
        public readonly string $parentId,
        private readonly string $attribute,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function forRule(string $ruleId, string $parentId): self
    {
        return new self($ruleId, $parentId, 'parent_rule_id', __('That would put this rule inside itself.'));
    }

    public static function forPhase(string $phaseId, string $parentId): self
    {
        return new self($phaseId, $parentId, 'parent_phase_id', __('That would put this phase inside itself.'));
    }

    public function field(): string
    {
        return $this->attribute;
    }
}
