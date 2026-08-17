<?php

namespace Modules\GameDesign\Application\Services;

use Modules\GameDesign\Domain\ValueObjects\MechanicSlug;
use Modules\GameDesign\Infrastructure\Persistence\Repositories\MechanicRepository;

/**
 * Turns a mechanic's name into an address nothing else is using.
 *
 * Uniqueness is not `MechanicSlug`'s business — that type only decides what a
 * well-formed address looks like, and resolving a collision needs to know what
 * already exists. This is where the two meet.
 *
 * Collisions are resolved by suffix rather than refused. A curator adding
 * "Drafting" when a "Drafting" already exists has almost certainly not noticed,
 * and the useful response is to let the save succeed at `drafting-2` and let
 * them see the duplicate in the list — refusing at the form would tell them
 * nothing about which existing term they had collided with.
 *
 * The database's unique index is what actually holds the rule; this makes the
 * common case not reach it.
 */
final class MechanicSlugAllocator
{
    /**
     * How many suffixes to try before giving up and letting the index decide.
     */
    private const ATTEMPTS = 50;

    public function __construct(private readonly MechanicRepository $mechanics) {}

    /**
     * Allocate an unused address derived from the given name.
     *
     * The ignored id is the mechanic being renamed, so that saving a term
     * without changing its name does not push it to `worker-placement-2`.
     */
    public function allocate(string $name, ?string $ignoreId = null): MechanicSlug
    {
        $base = MechanicSlug::fromName($name);

        if (! $this->mechanics->slugExists($base, $ignoreId)) {
            return $base;
        }

        for ($suffix = 2; $suffix <= self::ATTEMPTS; $suffix++) {
            $candidate = $base->withSuffix($suffix);

            if (! $this->mechanics->slugExists($candidate, $ignoreId)) {
                return $candidate;
            }
        }

        return $base->withSuffix(bin2hex(random_bytes(4)));
    }
}
