<?php

namespace Modules\PrototypeIteration\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\PrototypeRepository;

/**
 * A prototype's states, newest first.
 *
 * Newest first because a prototype's versions are a stack rather than a sequence: what
 * somebody reaching for the list wants is the current build, and v1 is history. Each
 * carries its artifact and iteration counts, because both are what the version list is
 * scanned for — "which of these has the print sheets, and which did we actually test?".
 *
 * @see PrototypeRepository::versionsOf()
 */
final class GetPrototypeVersions
{
    public function __construct(private readonly PrototypeRepository $prototypes) {}

    /**
     * @return Collection<int, PrototypeVersion>
     */
    public function handle(Prototype $prototype): Collection
    {
        return $this->prototypes->versionsOf($prototype);
    }
}
