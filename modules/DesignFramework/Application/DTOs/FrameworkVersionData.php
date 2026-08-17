<?php

namespace Modules\DesignFramework\Application\DTOs;

/**
 * The validated input for opening or editing an edition of a framework.
 *
 * Both operations take the same two fields, so they share a DTO. What they do not
 * share is the version *number*: it is absent here on purpose, because it is
 * allocated by `CreateFrameworkVersion` and never supplied by a caller. Version
 * numbers are cited by the games that adopt them, so a client allowed to name its
 * own could overwrite the meaning of an edition somebody is following.
 *
 * There is no status either. Every version starts as a draft and is published by
 * an explicit command.
 */
final readonly class FrameworkVersionData
{
    /**
     * @param  list<string>  $provided  the input keys the caller actually sent
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public array $provided = [],
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            name: FrameworkInput::text($input, 'name'),
            description: FrameworkInput::text($input, 'description'),
            provided: array_keys($input),
        );
    }

    /**
     * Determine whether the caller sent the given field at all.
     */
    public function sent(string $field): bool
    {
        return in_array($field, $this->provided, strict: true);
    }
}
