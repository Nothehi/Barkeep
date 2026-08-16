<?php

namespace Modules\GameDesign\Application\Services;

use Illuminate\Support\Str;
use Modules\GameDesign\Domain\ValueObjects\GameSlug;
use Modules\GameDesign\Infrastructure\Persistence\Repositories\GameRepository;
use Modules\Workspace\Domain\Models\Workspace;

/**
 * Turns a game's name into an address that is free inside its workspace.
 *
 * Only ever applied to addresses the platform derived itself. An address
 * somebody typed is never quietly renamed — that collision is reported to
 * them as a validation error, because the address they chose is part of what
 * they asked for.
 *
 * Uniqueness is per workspace, so a name that collides in one studio may be
 * perfectly free in another.
 */
final class GameSlugGenerator
{
    /**
     * How many numbered candidates to try before giving up on tidiness.
     */
    private const MAX_ATTEMPTS = 100;

    public function __construct(private readonly GameRepository $games) {}

    /**
     * Find a free address in the workspace, based on the given game name.
     *
     * Candidates are tried in a fixed order — `bears-bridges`,
     * `bears-bridges-2`, `bears-bridges-3` — so the same name always yields
     * the same address for a given set of existing games.
     */
    public function forName(Workspace $workspace, string $name): GameSlug
    {
        $base = GameSlug::fromName($name);

        if (! $this->games->slugExists($workspace, $base)) {
            return $base;
        }

        for ($suffix = 2; $suffix <= self::MAX_ATTEMPTS; $suffix++) {
            $candidate = $base->withSuffix($suffix);

            if (! $this->games->slugExists($workspace, $candidate)) {
                return $candidate;
            }
        }

        /**
         * A hundred games in one workspace already share this name.
         * Readability has lost either way, so stop counting and take
         * something that will not collide.
         */
        return $base->withSuffix(Str::lower(Str::random(8)));
    }
}
