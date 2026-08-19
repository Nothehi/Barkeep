<?php

namespace Modules\PrototypeIteration\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\PrototypeIteration\Domain\Models\PrototypeArtifact;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\PrototypeRepository;

/**
 * The files attached to one state of a prototype, in upload order.
 *
 * Forwards rather than newest first, unlike the version list. Artifacts are a set rather
 * than a stack — somebody uploads the card fronts, then the backs, then the rulebook — and
 * reading them in that order is how they think of the group.
 *
 * @see PrototypeRepository::artifactsOf()
 */
final class GetPrototypeArtifacts
{
    public function __construct(private readonly PrototypeRepository $prototypes) {}

    /**
     * @return Collection<int, PrototypeArtifact>
     */
    public function handle(PrototypeVersion $version): Collection
    {
        return $this->prototypes->artifactsOf($version);
    }
}
