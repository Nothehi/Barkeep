<?php

namespace Modules\DesignFramework\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;

/**
 * A framework's editions, oldest first.
 *
 * Read as a history — v1, then v2, then v3 — which is why the newest is at the bottom
 * rather than the top. The adoption count comes with each one, because "who is on v1?" is
 * the question a framework author needs answered before publishing v2.
 */
final class GetFrameworkVersions
{
    public function __construct(private readonly FrameworkRepository $frameworks) {}

    /**
     * @return Collection<int, FrameworkVersion>
     */
    public function handle(Framework $framework, bool $includeDrafts = false): Collection
    {
        return $this->frameworks->versionsOf($framework, $includeDrafts);
    }
}
