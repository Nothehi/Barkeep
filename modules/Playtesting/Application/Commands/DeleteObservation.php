<?php

namespace Modules\Playtesting\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Application\Services\PlaytestModificationGuard;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestSession;

/**
 * Withdraw an observation while the session is still open.
 *
 * The counterpart to correcting one, and bounded the same way. Something typed
 * twice or into the wrong session should be removable in the moment; once the
 * session has ended, nothing can be taken out of it.
 *
 * That boundary is what makes a completed session trustworthy as evidence. If
 * observations could be removed afterwards, a designer reading a playtest back
 * would have no way to know whether they were reading everything that was
 * noticed or only the parts somebody still agreed with.
 */
final class DeleteObservation
{
    public function __construct(private readonly PlaytestModificationGuard $guard) {}

    public function handle(User $actor, PlaytestSession $session, PlaytestObservation $observation): void
    {
        $this->guard->ensureSessionAcceptsEvidence($session);

        $observation->delete();
    }
}
