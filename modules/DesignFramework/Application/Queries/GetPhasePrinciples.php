<?php

namespace Modules\DesignFramework\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\DesignPrinciple;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;

/**
 * The design rules of a version, or of one of its phases.

 * Two questions, one class, because the difference between them is a single argument.
 * With a phase, this is what a phase page renders; without one, it is the whole
 * version's set — which is what the framework builder and the JSON API's
 * `/versions/{version}/principles` need.
 *
 * The version-wide answer is ordered by phase and then by position, with the phase-less
 * content first, so a single flat list renders as a hierarchy without the client sorting
 * anything.
 *
 * Always version-scoped, and the version is a required argument rather than a filter —
 * there is no "all principles" query to call by mistake.
 *
 * Draft and archived content is excluded unless the caller asks for it, and only the
 * framework builder does, after the policy has confirmed the caller administers
 * frameworks. Resolution is unauthorized on purpose: finding content and deciding who may
 * see it are separate steps, and every caller runs the policy first.
 */
final class GetPhasePrinciples
{
    public function __construct(private readonly FrameworkRepository $frameworks) {}

    /**
     * @return Collection<int, DesignPrinciple>
     */
    public function handle(
        FrameworkVersion $version,
        ?DesignPhaseDefinition $phase = null,
        bool $includeUnpublished = false,
    ): Collection {
        /** @var Collection<int, DesignPrinciple> $content */
        $content = $phase === null
            ? $this->frameworks->contentOf($version, DesignPrinciple::class, $includeUnpublished)
            : $this->frameworks->contentInPhase($phase, DesignPrinciple::class, $includeUnpublished);

        return $content;
    }
}
