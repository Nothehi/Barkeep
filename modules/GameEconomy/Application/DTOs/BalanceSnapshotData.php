<?php

namespace Modules\GameEconomy\Application\DTOs;

/**
 * The validated input required to freeze a configuration.
 *
 * Two fields, both about labelling. What a snapshot *contains* is never supplied
 * — it is read from the live tables by the command — because a snapshot whose
 * contents a caller could choose would not be a record of anything.
 */
final readonly class BalanceSnapshotData
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            name: EconomyInput::requiredText($input, 'name'),
            description: EconomyInput::text($input, 'description'),
        );
    }
}
