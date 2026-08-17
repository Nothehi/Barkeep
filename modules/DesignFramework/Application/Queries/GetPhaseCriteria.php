<?php

namespace Modules\DesignFramework\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\DesignFramework\Domain\Models\DesignCriterion;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;

/**
 * The assessment questions of a version, or of one of its phases.

 * Returns the questions, never anybody's answers. What a particular game scored lives in
 * `criterion_evaluations` and is read by {@see GetCriterionEvaluations} — keeping the two
 * queries apart is the read-side half of the separation section 22 calls critical.
 *
 * Always version-scoped, and the version is a required argument rather than a filter —
 * there is no "all criteria" query to call by mistake.
 *
 * Draft and archived content is excluded unless the caller asks for it, and only the
 * framework builder does, after the policy has confirmed the caller administers
 * frameworks. Resolution is unauthorized on purpose: finding content and deciding who may
 * see it are separate steps, and every caller runs the policy first.
 */
final class GetPhaseCriteria
{
    public function __construct(private readonly FrameworkRepository $frameworks) {}

    /**
     * @return Collection<int, DesignCriterion>
     */
    public function handle(
        FrameworkVersion $version,
        ?DesignPhaseDefinition $phase = null,
        bool $includeUnpublished = false,
    ): Collection {
        /** @var Collection<int, DesignCriterion> $content */
        $content = $phase === null
            ? $this->frameworks->contentOf($version, DesignCriterion::class, $includeUnpublished)
            : $this->frameworks->contentInPhase($phase, DesignCriterion::class, $includeUnpublished);

        return $content;
    }
}
