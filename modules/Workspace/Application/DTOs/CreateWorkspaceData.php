<?php

namespace Modules\Workspace\Application\DTOs;

use Modules\Workspace\Domain\ValueObjects\WorkspaceSlug;

/**
 * The validated input required to open a new workspace.
 *
 * The slug is optional because leaving it out means something different from
 * supplying one: an absent slug is derived from the name and may be adjusted
 * to avoid a collision, whereas a supplied slug is taken literally.
 */
final readonly class CreateWorkspaceData
{
    public function __construct(
        public string $name,
        public ?WorkspaceSlug $slug = null,
        public ?string $description = null,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $slug = isset($input['slug']) && $input['slug'] !== '' ? (string) $input['slug'] : null;
        $description = isset($input['description']) && $input['description'] !== '' ? (string) $input['description'] : null;

        return new self(
            name: trim((string) $input['name']),
            slug: $slug === null ? null : WorkspaceSlug::fromString($slug),
            description: $description,
        );
    }
}
