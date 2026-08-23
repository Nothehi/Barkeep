<?php

namespace Modules\GameRules\Domain\ValueObjects;

/**
 * How much of a rule system there is, counted.
 *
 * The row of numbers across the top of the rules dashboard: twenty-four rules,
 * eight mechanics, seven phases, sixteen actions, four warnings, one error.
 *
 * Counted rather than loaded. A dashboard that fetched every rule to render the
 * number twenty-four would be reading the whole system to draw a heading, and
 * these are the numbers a designer looks at before deciding whether to open
 * anything at all.
 */
final readonly class RuleSetSummary
{
    public function __construct(
        public int $rules,
        public int $mechanics,
        public int $phases,
        public int $transitions,
        public int $actions,
        public int $requirements,
        public int $conditions,
        public int $conditionGroups,
        public int $effects,
        public int $triggers,
        public int $victoryConditions,
        public int $defeatConditions,
        public int $endConditions,
        public int $references,
        public int $warnings,
        public int $errors,
    ) {}

    /**
     * Determine whether anything has been written down yet.
     *
     * What the dashboard uses to choose between the empty state and the real
     * one. Counts only the four things a rule system is made of — a set with
     * three conditions and nothing to use them on is still empty.
     */
    public function isEmpty(): bool
    {
        return $this->rules === 0
            && $this->phases === 0
            && $this->actions === 0
            && $this->mechanics === 0;
    }

    /**
     * Determine whether the validator found anything that cannot work.
     *
     * The check `ActivateRuleSet` runs: "these are the rules now" is a claim a
     * rule which is its own ancestor makes false.
     */
    public function hasErrors(): bool
    {
        return $this->errors > 0;
    }
}
