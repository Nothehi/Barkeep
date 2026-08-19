<?php

namespace Modules\GameEconomy\Application\DTOs;

use Modules\GameEconomy\Domain\ValueObjects\Quantity;

/**
 * The validated input required to state one value differently in a scenario.
 *
 * Two fields, and that is the whole of it: which number, and what it is instead.
 * Nothing here can reach the base variable — a scenario's values live in their
 * own table, so "a scenario never modifies the base" is a property of where the
 * data goes rather than a rule this DTO has to respect.
 */
final readonly class ScenarioVariableData
{
    public function __construct(
        public string $balanceVariableId,
        public Quantity $value,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            balanceVariableId: EconomyInput::identifier($input, 'balance_variable_id') ?? '',
            value: EconomyInput::requiredAmount($input, 'value'),
        );
    }
}
