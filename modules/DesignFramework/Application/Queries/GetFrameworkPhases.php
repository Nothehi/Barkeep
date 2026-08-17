<?php

namespace Modules\DesignFramework\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;

/**
 * The stages of one edition, in the order a designer works through them.
 *
 * Ordered by position and by nothing else — see `Position` for why an id or a timestamp
 * would put a phase inserted later at the end forever.
 *
 * Draft and archived phases are excluded unless the caller asks, and only the framework
 * builder does.
 */
final class GetFrameworkPhases
{
    public function __construct(private readonly FrameworkRepository $frameworks) {}

    /**
     * @return Collection<int, DesignPhaseDefinition>
     */
    public function handle(FrameworkVersion $version, bool $includeUnpublished = false): Collection
    {
        return $this->frameworks->phasesOf($version, $includeUnpublished);
    }
}
