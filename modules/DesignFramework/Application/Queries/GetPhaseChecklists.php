<?php

namespace Modules\DesignFramework\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;

/**
 * The readiness gates of a version, or of one of its phases.
 *
 * The one content query that loads children. A checklist without its items tells a
 * designer nothing — "prototype readiness" is a heading, and the requirements under it are
 * the content — so the items come with it rather than being fetched per list, which would
 * be a query per checklist on a phase page.
 *
 * Returns the requirements, never which of them a game has ticked. That is
 * {@see GetChecklistProgress}, which pairs these lists with the game's own completions.
 */
final class GetPhaseChecklists
{
    public function __construct(private readonly FrameworkRepository $frameworks) {}

    /**
     * @return Collection<int, Checklist>
     */
    public function handle(
        FrameworkVersion $version,
        ?DesignPhaseDefinition $phase = null,
        bool $includeUnpublished = false,
    ): Collection {
        return $phase === null
            ? $this->frameworks->checklistsWithItems($version, $includeUnpublished)
            : $this->frameworks->checklistsInPhase($phase, $includeUnpublished);
    }
}
