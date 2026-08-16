<?php

namespace Modules\Playtesting\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Application\DTOs\CreateObservationData;
use Modules\Playtesting\Application\Services\ParticipantResolver;
use Modules\Playtesting\Application\Services\PlaytestModificationGuard;
use Modules\Playtesting\Domain\Events\ObservationCreated;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestSession;

/**
 * Record something a designer noticed during a session.
 *
 * The most-used command in the module, and the one whose cost is measured in
 * seconds rather than queries. An observation is typed with one hand while the
 * game carries on with the other; anything that makes it slower means fewer
 * observations get recorded, and unrecorded observations are the ones
 * designers forget by the time they get home.
 *
 * `observed_at` defaults to now while the session is running, so the timeline
 * orders itself without anybody being asked for a time. It stays null for a
 * session that has not started — an observation written during planning did
 * not happen at a moment, and inventing one would put a fiction into the
 * account.
 *
 * The participant is checked rather than trusted. It is the one identifier in
 * the module that arrives in a request body without a route binding to scope
 * it, and attributing one session's observation to another session's
 * participant would produce a record that reads perfectly and is false.
 */
final class CreateObservation
{
    public function __construct(
        private readonly PlaytestModificationGuard $guard,
        private readonly ParticipantResolver $participants,
    ) {}

    public function handle(User $observer, PlaytestSession $session, CreateObservationData $data): PlaytestObservation
    {
        $this->guard->ensureSessionAcceptsEvidence($session);

        $participant = $this->participants->resolve($session, $data->participantId);

        $observation = new PlaytestObservation;

        $observation->fill(['content' => $data->content]);

        $observation->session_id = $session->getKey();
        $observation->participant_id = $participant?->getKey();
        $observation->category = $data->category;
        $observation->observed_at = $data->observedAt ?? ($session->isRunning() ? now()->toImmutable() : null);
        $observation->created_by = $observer->id;

        $observation->save();

        $observation->setRelation('session', $session);
        $observation->setRelation('participant', $participant);
        $observation->setRelation('creator', $observer);

        event(new ObservationCreated(
            observationId: $observation->id,
            sessionId: $session->getKey(),
            playtestId: $session->playtest_id,
            gameId: $session->playtest->game_id ?? '',
            category: $observation->category,
            createdBy: $observer->id,
        ));

        return $observation;
    }
}
