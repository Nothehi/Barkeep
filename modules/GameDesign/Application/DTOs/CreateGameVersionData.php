<?php

namespace Modules\GameDesign\Application\DTOs;

/**
 * The validated input required to record a new iteration of a game.
 *
 * There is no version number here, and that absence is the rule: numbers are
 * allocated by the module in sequence, so no caller can claim v999 or reuse a
 * number that already means something.
 */
final readonly class CreateGameVersionData
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
        $name = isset($input['name']) && $input['name'] !== '' ? trim((string) $input['name']) : null;
        $description = isset($input['description']) && $input['description'] !== '' ? (string) $input['description'] : null;

        return new self(
            name: $name,
            description: $description,
        );
    }
}
