<?php

namespace Modules\GameRules\Application\DTOs;

/**
 * The validated input required to start or rename a rule set.
 *
 * Nothing about the rules is here, and that is deliberate: a rule set is created
 * empty and then written. A create form that asked for phases or a victory
 * condition would be asking a designer to have finished before they had started.
 *
 * The status is absent too. Activating and archiving are actions with their own
 * endpoints and their own rules — one of them irreversible — rather than a field
 * a PATCH can set.
 */
final readonly class RuleSetData
{
    /**
     * @param  list<string>  $sentFields  the fields the request actually mentioned
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
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
            name: RuleInput::text($input, 'name'),
            description: RuleInput::text($input, 'description'),
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
