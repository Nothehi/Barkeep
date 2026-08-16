<?php

namespace Modules\Playtesting\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Application\DTOs\CreateSessionData;
use Modules\Playtesting\Application\Services\PlaytestModificationGuard;
use Modules\Playtesting\Domain\Enums\PlaytestSessionStatus;
use Modules\Playtesting\Domain\Events\PlaytestSessionCreated;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Domain\Models\PlaytestSession;

/**
 * Schedule another sitting of a playtest.
 *
 * Nothing is required, and that is a usability decision with teeth. The common
 * case is a designer about to start a session in the next thirty seconds; a
 * form that insists on a location and a planned time first is a form that gets
 * abandoned, and the session gets run without being recorded.
 *
 * The real timestamps are conspicuously absent from the input. `started_at`
 * and `ended_at` are written by the commands that start and end a session,
 * from the clock, so they record what happened rather than what somebody
 * typed afterwards.
 */
final class CreatePlaytestSession
{
    public function __construct(private readonly PlaytestModificationGuard $guard) {}

    public function handle(User $creator, Playtest $playtest, CreateSessionData $data): PlaytestSession
    {
        $this->guard->ensurePlaytestIsModifiable($playtest);

        $session = new PlaytestSession;

        $session->fill([
            'location' => $data->location,
            'notes' => $data->notes,
        ]);

        $session->playtest_id = $playtest->getKey();
        $session->status = PlaytestSessionStatus::default();
        $session->planned_at = $data->plannedAt;
        $session->created_by = $creator->id;

        $session->save();

        $session->setRelation('playtest', $playtest);
        $session->setRelation('creator', $creator);

        event(new PlaytestSessionCreated(
            sessionId: $session->id,
            playtestId: $playtest->getKey(),
            gameId: $playtest->game_id,
            createdBy: $creator->id,
        ));

        return $session;
    }
}
