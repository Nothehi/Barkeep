<?php

namespace Modules\GameEconomy\Application\DTOs;

/**
 * The validated input required to describe a hypothetical.
 *
 * The overrides are absent. Setting a value in a scenario is its own endpoint,
 * because that is how a scenario is actually built: somebody names "Rich
 * economy" and then works through the numbers one at a time, comparing as they
 * go.
 */
final readonly class BalanceScenarioData
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public bool $descriptionWasSent = false,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            name: EconomyInput::text($input, 'name'),
            description: EconomyInput::text($input, 'description'),
            descriptionWasSent: EconomyInput::has($input, 'description'),
        );
    }
}
