<?php

namespace Modules\DesignFramework\Application\DTOs;

/**
 * The validated input required to start a new methodology.
 *
 * The address is optional, and when it is absent the command derives one from the
 * name. There is no status: every framework starts as a draft, and anything sent
 * would be ignored — so it is not accepted.
 */
final readonly class CreateFrameworkData
{
    public function __construct(
        public string $name,
        public ?string $slug = null,
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
            name: FrameworkInput::requiredText($input, 'name'),
            slug: FrameworkInput::text($input, 'slug'),
            description: FrameworkInput::text($input, 'description'),
        );
    }
}
