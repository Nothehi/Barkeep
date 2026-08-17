<?php

namespace Modules\GameDesign\Application\Queries;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\GameDesign\Infrastructure\Persistence\Repositories\MechanicRepository;

/**
 * The design vocabulary, in reading order.
 *
 * Deliberately unscoped — the vocabulary is the platform's, and every signed in
 * account reads the same list. This is the one query in the module that takes
 * no workspace and no game, which is worth noticing rather than glossing: it is
 * safe precisely because a mechanic carries nothing about anybody.
 *
 * Retired terms are excluded unless asked for. A designer picking mechanics
 * should not be offered a word the platform has withdrawn; a curator looking at
 * the vocabulary needs to see what they retired.
 */
final class GetMechanics
{
    public function __construct(private readonly MechanicRepository $mechanics) {}

    /**
     * @return Collection<int, Mechanic>
     */
    public function handle(bool $includeArchived = false): Collection
    {
        return $this->mechanics->all($includeArchived);
    }
}
