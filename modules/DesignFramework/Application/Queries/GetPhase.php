<?php

namespace Modules\DesignFramework\Application\Queries;

use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\ValueObjects\ContentSlug;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;

/**
 * One stage of one edition, by address.
 *
 * The lookup behind the `{phase}` route binding, and scoped to the version for the same
 * reason a version is scoped to its framework: `core-loop` is only unique inside one
 * edition, and every framework version may name its stages differently.
 */
final class GetPhase
{
    public function __construct(private readonly FrameworkRepository $frameworks) {}

    public function handle(FrameworkVersion $version, ContentSlug $slug): ?DesignPhaseDefinition
    {
        return $this->frameworks->findPhase($version, $slug);
    }
}
