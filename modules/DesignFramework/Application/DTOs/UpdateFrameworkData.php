<?php

namespace Modules\DesignFramework\Application\DTOs;

/**
 * The validated input for changing a framework, and a record of what was sent.
 *
 * The list of provided keys distinguishes "left this field alone" from "cleared
 * this field", which matters for the description: a request that only meant to
 * rename the framework would otherwise look identical to one that also blanked its
 * description.
 *
 * The form requests validate with `sometimes`, so a key reaching here is a key the
 * caller actually sent.
 */
final readonly class UpdateFrameworkData
{
    /**
     * @param  list<string>  $provided  the input keys the caller actually sent
     */
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
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
            name: array_key_exists('name', $input) ? FrameworkInput::requiredText($input, 'name') : null,
            slug: FrameworkInput::text($input, 'slug'),
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
