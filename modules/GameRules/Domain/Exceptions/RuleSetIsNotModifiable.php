<?php

namespace Modules\GameRules\Domain\Exceptions;

use Modules\GameRules\Domain\Enums\RuleSetStatus;

/**
 * Raised when anything is written to a rule set that may no longer change.
 *
 * Covers the whole system, not just the rule set row: rules, mechanics, phases,
 * transitions, actions, requirements, conditions, groups, effects, triggers,
 * outcomes and references all belong to a rule set, and a set that is not a
 * draft refuses every one of them.
 *
 * This is section 55 of the brief at its single enforcement point, and the
 * behaviour is stricter than GameEconomy's namesake on purpose. An active
 * balance profile is still tunable — tuning is what a studio does to the numbers
 * in play. An active rule *set* is not editable, because the rules are what a
 * session was played under: changing them rewrites what every playtest against
 * them means. The way forward is `CloneRuleSet`, which is cheap and says what
 * the designer means.
 *
 * Reading is untouched, which is what keeps a two-year-old rule system legible.
 */
final class RuleSetIsNotModifiable extends RuleSystemViolation
{
    private function __construct(public readonly ?RuleSetStatus $ruleSetStatus, string $message)
    {
        parent::__construct($message);
    }

    public static function forStatus(RuleSetStatus $status): self
    {
        return new self($status, $status->deniedReason());
    }

    /**
     * Raised when the game around the rule set is what refused.
     *
     * Reported as a rule set problem because that is the object the caller was
     * acting on, and carries GameDesign's own wording so they are still told the
     * real reason.
     */
    public static function becauseGameIsClosed(string $reason): self
    {
        return new self(null, $reason);
    }

    public function status(): int
    {
        return 409;
    }
}
