<?php

namespace Modules\GameDesign\Application\DTOs;

use Modules\GameDesign\Domain\Enums\MechanicCategory;

/**
 * What a term in the vocabulary is written from.
 *
 * No address. A mechanic's slug is derived from its name and made unique by
 * `MechanicSlugAllocator`, so a curator names the thing and the platform
 * decides what to call it in a URL — which is what keeps two curators typing
 * "Worker Placement" and "worker placement" from producing two rows.
 *
 * No status either. Adding a term publishes it, and retiring one is its own
 * action with its own rule; letting the status ride along with a rename is
 * exactly how a retired mechanic quietly comes back.
 */
final readonly class MechanicData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public MechanicCategory $category,
    ) {}

    /**
     * Build the data from validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $description = trim((string) ($input['description'] ?? ''));

        return new self(
            name: trim((string) $input['name']),
            description: $description === '' ? null : $description,
            category: $input['category'] instanceof MechanicCategory
                ? $input['category']
                : MechanicCategory::from((string) $input['category']),
        );
    }
}
