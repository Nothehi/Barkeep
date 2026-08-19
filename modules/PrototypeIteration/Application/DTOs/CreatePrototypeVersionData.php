<?php

namespace Modules\PrototypeIteration\Application\DTOs;

/**
 * The validated input required to cut a new state of a prototype.
 *
 * There is no version number here, and that absence is the rule: numbers are
 * allocated by the module in sequence, so no caller can claim v999 or reuse a
 * number that already means something to three iterations.
 *
 * Both fields are optional. Cutting the next version is meant to be nearly
 * frictionless — the whole immutability arrangement depends on a designer
 * reaching for a new version rather than editing the last one, and a required
 * form would push them the other way.
 */
final readonly class CreatePrototypeVersionData
{
    public function __construct(
        public ?string $name = null,
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
            name: IterationInput::text($input, 'name'),
            description: IterationInput::text($input, 'description'),
        );
    }
}
