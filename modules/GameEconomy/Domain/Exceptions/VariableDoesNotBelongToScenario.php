<?php

namespace Modules\GameEconomy\Domain\Exceptions;

/**
 * Raised when a scenario is asked to override a variable from another profile.
 *
 * A scenario is a set of overrides on one configuration. Letting it name a
 * variable from a different profile would produce a scenario that changes
 * nothing anybody can see — the override would be stored, the profile it applies
 * to would not contain the variable, and the difference would only surface as a
 * number that refused to move.
 */
final class VariableDoesNotBelongToScenario extends EconomyRuleViolation
{
    private function __construct(
        public readonly string $scenarioId,
        public readonly string $variableId,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function forPair(string $scenarioId, string $variableId): self
    {
        return new self($scenarioId, $variableId, __('That variable belongs to a different balance profile.'));
    }

    public function field(): string
    {
        return 'balance_variable_id';
    }
}
