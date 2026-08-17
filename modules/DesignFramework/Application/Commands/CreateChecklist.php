<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\ContentData;
use Modules\DesignFramework\Application\Services\ContentWriter;
use Modules\DesignFramework\Domain\Events\ChecklistCreated;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\Identity\Domain\Models\User;

/**
 * Add a readiness gate to a draft edition.
 *
 * A checklist starts empty and gains items through {@see CreateChecklistItem}. Creating
 * both here would mean guessing at the requirements, and "prototype readiness" with an
 * invented item in it is worse than one with none.
 *
 * It is the only content type with children of its own, which is why its items have
 * their own commands and their own ordering rather than being edited as a blob.
 *
 * The mechanics — proving the version is still a draft, resolving the phase through it,
 * deriving an address, allocating a position — are `ContentWriter`'s, shared with the
 * other four content types. What is specific to a checklist is the body field below.
 */
final class CreateChecklist
{
    public function __construct(private readonly ContentWriter $writer) {}

    public function handle(User $creator, FrameworkVersion $version, ContentData $data): Checklist
    {
        /** @var Checklist $checklist */
        $checklist = $this->writer->create($version, Checklist::class, $data, [
            'description' => $data->description,
        ]);

        event(new ChecklistCreated(
            checklistId: $checklist->id,
            frameworkVersionId: $version->id,
            phaseId: $checklist->phase_id,
            slug: $checklist->slug,
            createdBy: $creator->id,
        ));

        return $checklist;
    }
}
