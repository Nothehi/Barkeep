<?php

namespace Modules\Workspace\Application\Services;

use Illuminate\Support\Str;
use Modules\Workspace\Domain\ValueObjects\WorkspaceSlug;
use Modules\Workspace\Infrastructure\Persistence\Repositories\WorkspaceRepository;

/**
 * Turns a workspace name into an address that is free to use.
 *
 * Only ever applied to addresses the platform derived itself. A slug somebody
 * typed is never quietly renamed — that collision is reported back to them as
 * a validation error, because the address they chose is part of what they
 * asked for.
 */
final class WorkspaceSlugGenerator
{
    /**
     * How many numbered candidates to try before giving up on tidiness.
     */
    private const MAX_ATTEMPTS = 100;

    public function __construct(private readonly WorkspaceRepository $workspaces) {}

    /**
     * Find a free address based on the given workspace name.
     *
     * Candidates are tried in a fixed order — `studio`, `studio-2`, `studio-3`
     * — so the same name always yields the same address for a given set of
     * existing workspaces.
     */
    public function forName(string $name): WorkspaceSlug
    {
        $base = WorkspaceSlug::fromName($name);

        if (! $this->workspaces->slugExists($base)) {
            return $base;
        }

        for ($suffix = 2; $suffix <= self::MAX_ATTEMPTS; $suffix++) {
            $candidate = $base->withSuffix($suffix);

            if (! $this->workspaces->slugExists($candidate)) {
                return $candidate;
            }
        }

        /**
         * A hundred workspaces already share this name. Readability has lost
         * either way, so stop counting and take something that will not
         * collide.
         */
        return $base->withSuffix(Str::lower(Str::random(8)));
    }
}
