<?php

namespace Modules\PrototypeIteration\Application\Queries;

use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Domain\Models\PrototypeVersion;
use Modules\PrototypeIteration\Domain\ValueObjects\PrototypeVersionNumber;
use Modules\PrototypeIteration\Infrastructure\Persistence\Repositories\PrototypeRepository;

/**
 * One of a prototype's states, by its number.
 *
 * By number rather than by id, because that is how the route addresses it and how a
 * designer says it out loud. The number is only meaningful inside one prototype, which is
 * exactly why the lookup takes one — and why a version number from another prototype
 * cannot be reached from here at all.
 */
final class GetPrototypeVersion
{
    public function __construct(private readonly PrototypeRepository $prototypes) {}

    public function handle(Prototype $prototype, PrototypeVersionNumber $number): ?PrototypeVersion
    {
        return $this->prototypes->findVersionForPrototype($prototype, $number);
    }
}
