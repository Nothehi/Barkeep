<?php

namespace Modules\Playtesting\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Application\DTOs\CreateObservationData;
use Modules\Playtesting\Application\Services\ParticipantResolver;
use Modules\Playtesting\Application\Services\PlaytestModificationGuard;
use Modules\Playtesting\Domain\Models\PlaytestObservation;
use Modules\Playtesting\Domain\Models\PlaytestSession;

/**
 * Correct an observation while the session is still open.
 *
 * Typing at a table produces typos, half-sentences and things filed under the
 * wrong heading, so corrections have to be possible. They stop being possible
 * when the session ends, which is what keeps the record datable: everything in
 * a completed session was written while it was open.
 *
 * Anybody who can add an observation can correct one. An observation is a note
 * somebody made about a shared evening, not a signed statement, and making
 * corrections depend on who happened to be holding the laptop would get in the
 * way for no gain.
 *
 * The whole observation is replaced rather than patched. Every field is on the
 * form, so a partial update would only add a way for the two to disagree.
 */
final class UpdateObservation
{
    public function __construct(
        private readonly PlaytestModificationGuard $guard,
        private readonly ParticipantResolver $participants,
    ) {}

    public function handle(
        User $actor,
        PlaytestSession $session,
        PlaytestObservation $observation,
        CreateObservationData $data,
    ): PlaytestObservation {
        $this->guard->ensureSessionAcceptsEvidence($session);

        $participant = $this->participants->resolve($session, $data->participantId);

        $observation->fill(['content' => $data->content]);

        $observation->participant_id = $participant?->getKey();
        $observation->category = $data->category;
        $observation->observed_at = $data->observedAt;

        $observation->save();

        $observation->setRelation('participant', $participant);

        return $observation;
    }
}
