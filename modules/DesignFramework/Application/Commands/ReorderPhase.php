<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\Services\ContentSequencer;
use Modules\DesignFramework\Application\Services\FrameworkModificationGuard;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Move a stage to a different place in the arc.
 *
 * The position arrives from the client and is validated against the version's actual
 * phase count rather than clamped — see `Position::within()`. All the arithmetic lives
 * in `ContentSequencer`; this command's job is to prove the version is still a draft
 * and to name the sibling set.
 *
 * Reordering is refused on a published version like any other edit. Phase order is
 * part of the methodology a game is following: shuffling it would reorder somebody's
 * remaining work without their knowing.
 */
final class ReorderPhase
{
    public function __construct(
        private readonly FrameworkModificationGuard $guard,
        private readonly FrameworkRepository $frameworks,
        private readonly ContentSequencer $sequencer,
    ) {}

    public function handle(User $actor, DesignPhaseDefinition $phase, int $position): DesignPhaseDefinition
    {
        $this->guard->ensurePhaseIsModifiable($phase);

        $version = $phase->version;

        if ($version !== null) {
            $this->sequencer->move($phase, $this->frameworks->phaseSiblings($version), $position);
        }

        return $phase;
    }
}
