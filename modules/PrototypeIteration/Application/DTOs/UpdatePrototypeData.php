<?php

namespace Modules\PrototypeIteration\Application\DTOs;

use Modules\PrototypeIteration\Domain\Enums\PrototypeType;

/**
 * The validated input for changing a prototype's own details.
 *
 * Every field is optional, and the flags beside them are what make a partial
 * update expressible. "Clear the description" and "leave the description alone"
 * both arrive as an absent value in a JSON body, and a DTO that could not tell
 * them apart would make one of the two impossible.
 *
 * The status is not here. Archiving is an action with its own endpoint and its
 * own rules, not a field somebody can set — which is what keeps an irreversible
 * move from being one PATCH away from a reversible one.
 *
 * Neither is the game version. A prototype records the design state it was built
 * from, and rewriting that afterwards would change what every iteration against
 * it says it was working with; the honest move is a new prototype.
 */
final readonly class UpdatePrototypeData
{
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?PrototypeType $type = null,
        public bool $changesDescription = false,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $type = IterationInput::identifier($input, 'type');

        return new self(
            name: array_key_exists('name', $input) ? IterationInput::requiredText($input, 'name') : null,
            description: IterationInput::text($input, 'description'),
            type: $type === null ? null : PrototypeType::tryFrom($type),
            changesDescription: array_key_exists('description', $input),
        );
    }

    /**
     * Determine whether the update would change anything at all.
     */
    public function isEmpty(): bool
    {
        return $this->name === null && $this->type === null && ! $this->changesDescription;
    }
}
