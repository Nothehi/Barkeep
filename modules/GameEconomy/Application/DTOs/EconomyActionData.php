<?php

namespace Modules\GameEconomy\Application\DTOs;

/**
 * The validated input required to declare or rename an action.
 *
 * What the action *does* is absent, and deliberately so: costs, rewards and
 * effects each have their own endpoints. An action is created empty and then
 * priced, which is how designers work — "we need a Build action" comes before
 * anybody has decided what it costs — and it is what keeps a create form from
 * demanding a resource list before the resources exist.
 */
final readonly class EconomyActionData
{
    /**
     * @param  list<string>  $sentFields  the fields the request actually mentioned
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?int $position = null,
        public array $sentFields = [],
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
            position: EconomyInput::has($input, 'position') ? EconomyInput::integer($input, 'position') : null,
            sentFields: array_keys($input),
        );
    }

    /**
     * Determine whether the request mentioned a field at all.
     */
    public function sent(string $field): bool
    {
        return in_array($field, $this->sentFields, strict: true);
    }
}
