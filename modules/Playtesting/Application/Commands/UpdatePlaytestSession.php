<?php

namespace Modules\Playtesting\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Application\DTOs\UpdateSessionData;
use Modules\Playtesting\Application\Services\PlaytestModificationGuard;
use Modules\Playtesting\Domain\Models\PlaytestSession;

/**
 * Change a session that has not ended yet.
 *
 * Notes are what this exists for. They accumulate as the session runs —
 * somebody types up what is happening around the individual observations — so
 * they are saved repeatedly rather than once at the end.
 *
 * The lifecycle timestamps are not writable here. Where a session took place
 * and what somebody wrote about it are editable facts; when it started and
 * ended are records of events, and those are written by the commands that
 * cause them.
 */
final class UpdatePlaytestSession
{
    public function __construct(private readonly PlaytestModificationGuard $guard) {}

    public function handle(User $actor, PlaytestSession $session, UpdateSessionData $data): PlaytestSession
    {
        $this->guard->ensureSessionIsModifiable($session);

        $session->fill([
            'location' => $data->location,
            'notes' => $data->notes,
        ]);

        $session->planned_at = $data->plannedAt;

        $session->save();

        return $session;
    }
}
