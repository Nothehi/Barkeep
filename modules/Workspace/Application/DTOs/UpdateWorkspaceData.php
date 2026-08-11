<?php

namespace Modules\Workspace\Application\DTOs;

use Modules\Workspace\Domain\ValueObjects\WorkspaceSlug;

/**
 * The validated input required to change a workspace's own settings.
 *
 * Unlike creation, the slug is required: renaming a workspace must not
 * silently move its address, so the caller states the address it means to end
 * up with.
 */
final readonly class UpdateWorkspaceData
{
    public function __construct(
        public string $name,
        public WorkspaceSlug $slug,
        public ?string $description = null,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $description = isset($input['description']) && $input['description'] !== '' ? (string) $input['description'] : null;

        return new self(
            name: trim((string) $input['name']),
            slug: WorkspaceSlug::fromString((string) $input['slug']),
            description: $description,
        );
    }
}
