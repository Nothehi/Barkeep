<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\PhaseData;
use Modules\DesignFramework\Application\Services\FrameworkModificationGuard;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\Identity\Domain\Models\User;

/**
 * Change a stage's name, description or status.
 *
 * Not its position — that is {@see ReorderPhase}, and keeping them apart means a form
 * that saves a description cannot accidentally reshuffle the arc.
 *
 * Not its address either. It is derived once on creation and left alone, so a
 * bookmarked phase URL survives a rename.
 */
final class UpdatePhase
{
    public function __construct(private readonly FrameworkModificationGuard $guard) {}

    public function handle(User $actor, DesignPhaseDefinition $phase, PhaseData $data): DesignPhaseDefinition
    {
        $this->guard->ensurePhaseIsModifiable($phase);

        if ($data->sent('name') && $data->name !== null) {
            $phase->name = $data->name;
        }

        if ($data->sent('description')) {
            $phase->description = $data->description;
        }

        if ($data->status !== null) {
            $phase->status = $data->status;
        }

        $phase->save();

        return $phase;
    }
}
