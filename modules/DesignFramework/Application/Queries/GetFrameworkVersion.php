<?php

namespace Modules\DesignFramework\Application\Queries;

use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Domain\ValueObjects\FrameworkVersionNumber;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;

/**
 * One edition of a framework, by number.
 *
 * The lookup behind the `{version}` route binding. Scoped to the framework because that is
 * how a version is identified at all: "v1" means nothing without saying v1 of what, and a
 * version from another framework fails to resolve rather than being caught later by a
 * policy.
 */
final class GetFrameworkVersion
{
    public function __construct(private readonly FrameworkRepository $frameworks) {}

    public function handle(Framework $framework, FrameworkVersionNumber $number): ?FrameworkVersion
    {
        return $this->frameworks->findVersion($framework, $number);
    }
}
