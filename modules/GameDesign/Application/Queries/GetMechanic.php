<?php

namespace Modules\GameDesign\Application\Queries;

use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\GameDesign\Domain\ValueObjects\MechanicSlug;
use Modules\GameDesign\Infrastructure\Persistence\Repositories\MechanicRepository;

/**
 * One term, by its address.
 *
 * Used by the route binding. Null is an ordinary answer: an address that names
 * nothing is a 404 rather than an error, and the binding turns it into one.
 */
final class GetMechanic
{
    public function __construct(private readonly MechanicRepository $mechanics) {}

    public function handle(MechanicSlug $slug): ?Mechanic
    {
        return $this->mechanics->findBySlug($slug);
    }
}
