<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\PhaseData;
use Modules\DesignFramework\Application\Services\ContentSequencer;
use Modules\DesignFramework\Application\Services\ContentSlugAllocator;
use Modules\DesignFramework\Application\Services\FrameworkModificationGuard;
use Modules\DesignFramework\Domain\Enums\FrameworkContentStatus;
use Modules\DesignFramework\Domain\Events\PhaseCreated;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Add a stage to a draft edition's arc.
 *
 * Appended at the end. A framework author writing "Ideation, Concept, Core loop"
 * writes them in order, and the one who needs "Concept" in the middle a week later
 * reaches for {@see ReorderPhase} — which is a deliberate, visible action rather than
 * a side effect of a create.
 *
 * The address is derived from the name once and never re-derived, so
 * `/versions/1/phases/core-loop` survives the phase being renamed to "The core loop".
 */
final class CreatePhase
{
    public function __construct(
        private readonly FrameworkModificationGuard $guard,
        private readonly FrameworkRepository $frameworks,
        private readonly ContentSequencer $sequencer,
        private readonly ContentSlugAllocator $slugs,
    ) {}

    public function handle(User $creator, FrameworkVersion $version, PhaseData $data): DesignPhaseDefinition
    {
        $this->guard->ensureVersionIsModifiable($version);

        $name = (string) $data->name;

        $phase = new DesignPhaseDefinition;

        $phase->fill([
            'name' => $name,
            'description' => $data->description,
        ]);

        $phase->framework_version_id = $version->getKey();
        $phase->slug = $this->slugs->derive($this->frameworks->phaseSiblings($version), $name)->value;
        $phase->status = $data->status ?? FrameworkContentStatus::default();
        $phase->position = $this->sequencer->append($this->frameworks->phaseSiblings($version));

        $phase->save();

        $phase->setRelation('version', $version);

        event(new PhaseCreated(
            phaseId: $phase->id,
            frameworkVersionId: $version->id,
            slug: $phase->slug,
            position: $phase->position,
            createdBy: $creator->id,
        ));

        return $phase;
    }
}
