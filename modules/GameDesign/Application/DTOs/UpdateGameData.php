<?php

namespace Modules\GameDesign\Application\DTOs;

use Modules\GameDesign\Domain\ValueObjects\GameSlug;

/**
 * The validated input required to change a game's own metadata.
 *
 * Unlike creation the address is required: renaming a game must not silently
 * move its address, so the caller states the address it means to end up with.
 *
 * Status and design phase are absent on purpose. Both are lifecycle moves
 * with their own rules and their own commands, and letting them ride along
 * with a rename is exactly how a transition matrix gets bypassed.
 */
final readonly class UpdateGameData
{
    public function __construct(
        public string $name,
        public GameSlug $slug,
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
            slug: GameSlug::fromString((string) $input['slug']),
            description: $description,
        );
    }
}
