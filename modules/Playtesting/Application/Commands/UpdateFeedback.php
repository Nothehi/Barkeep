<?php

namespace Modules\Playtesting\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Application\DTOs\CreateFeedbackData;
use Modules\Playtesting\Application\Services\ParticipantResolver;
use Modules\Playtesting\Application\Services\PlaytestModificationGuard;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\Models\PlaytestSession;

/**
 * Correct a piece of feedback while the session is still open.
 *
 * Feedback is transcribed rather than typed by the person who said it, so
 * mishearing happens. Correcting it in the moment — usually by reading it back
 * to them — is how it gets right.
 *
 * Clearing the rating is a real edit rather than an omission: a null means the
 * participant did not put a number on it, which is different from a low score
 * and has to stay different, or every average is wrong.
 */
final class UpdateFeedback
{
    public function __construct(
        private readonly PlaytestModificationGuard $guard,
        private readonly ParticipantResolver $participants,
    ) {}

    public function handle(
        User $actor,
        PlaytestSession $session,
        PlaytestFeedback $feedback,
        CreateFeedbackData $data,
    ): PlaytestFeedback {
        $this->guard->ensureSessionAcceptsEvidence($session);

        $participant = $this->participants->resolve($session, $data->participantId);

        $feedback->fill(['content' => $data->content]);

        $feedback->participant_id = $participant?->getKey();
        $feedback->rating = $data->rating?->value;

        $feedback->save();

        $feedback->setRelation('participant', $participant);

        return $feedback;
    }
}
