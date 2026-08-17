<?php

namespace Modules\DesignFramework\Application\DTOs;

/**
 * The validated input for writing or editing one checklist requirement.
 *
 * Separate from {@see ContentData} because an item is not phase content: it has no
 * phase of its own, no status, and one field the others do not — whether it is
 * required, which is what decides whether it counts towards a game's progress.
 */
final readonly class ChecklistItemData
{
    /**
     * @param  list<string>  $provided  the input keys the caller actually sent
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public bool $required = true,
        public ?string $satisfiedBy = null,
        public array $provided = [],
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * `required` defaults to true when absent, because a checklist of optional
     * items is a list of suggestions.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            title: array_key_exists('title', $input) ? FrameworkInput::requiredText($input, 'title') : null,
            description: FrameworkInput::text($input, 'description'),
            required: FrameworkInput::flag($input, 'required', default: true),
            satisfiedBy: FrameworkInput::identifier($input, 'satisfied_by'),
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
