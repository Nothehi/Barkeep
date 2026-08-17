<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\Services\ContentSequencer;
use Modules\DesignFramework\Application\Services\FrameworkModificationGuard;
use Modules\DesignFramework\Domain\Models\DesignPrinciple;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Move a design rule among its siblings.
 *
 * Ordering matters even for content nobody ticks: principles are read top to bottom,
 * and the first one on a phase page is the one an author most wants read.
 *
 * The sibling set is scoped by phase as well as by version, so content filed under no
 * phase orders independently of content filed under one. Two principles in different
 * phases both being "position 1" is correct.
 *
 * All the arithmetic lives in `ContentSequencer`; this command proves the version is
 * still a draft and names the set.
 */
final class ReorderPrinciple
{
    public function __construct(
        private readonly FrameworkModificationGuard $guard,
        private readonly FrameworkRepository $frameworks,
        private readonly ContentSequencer $sequencer,
    ) {}

    public function handle(User $actor, DesignPrinciple $principle, int $position): DesignPrinciple
    {
        $this->guard->ensureContentIsModifiable($principle);

        $version = $principle->version;

        if ($version !== null) {
            $this->sequencer->move(
                $principle,
                $this->frameworks->contentSiblings(
                    $version,
                    DesignPrinciple::class,
                    $principle->phase_id === null ? null : (string) $principle->phase_id,
                ),
                $position,
            );
        }

        return $principle;
    }
}
