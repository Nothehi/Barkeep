<?php

namespace Modules\Playtesting\Application\Commands;

use Modules\Identity\Domain\Models\User;
use Modules\Playtesting\Application\DTOs\CreateFeedbackData;
use Modules\Playtesting\Application\Services\ParticipantResolver;
use Modules\Playtesting\Application\Services\PlaytestModificationGuard;
use Modules\Playtesting\Domain\Events\FeedbackCreated;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\Models\PlaytestSession;

/**
 * Record what a participant said about a session.
 *
 * Distinct from an observation, and kept distinct deliberately. "The scoring
 * confused them" is a designer's reading; "I didn't understand the scoring" is
 * a player's own words, and the second is worth more precisely because nobody
 * interpreted it first.
 *
 * Two people are involved and both are recorded. `participant_id` is who said
 * it — optional, because anonymous feedback is often the honest kind — and
 * `created_by` is whoever typed it in, which is almost always the facilitator
 * rather than the speaker. Collapsing the two would turn "the facilitator
 * wrote this down" into "the facilitator said this".
 *
 * The rating is a structured signal beside the words, not a substitute for
 * them. It exists so a playtest can report an average across sessions without
 * anybody reading every comment first; the comment is still where the useful
 * part is.
 */
final class CreateFeedback
{
    public function __construct(
        private readonly PlaytestModificationGuard $guard,
        private readonly ParticipantResolver $participants,
    ) {}

    public function handle(User $recorder, PlaytestSession $session, CreateFeedbackData $data): PlaytestFeedback
    {
        $this->guard->ensureSessionAcceptsEvidence($session);

        $participant = $this->participants->resolve($session, $data->participantId);

        $feedback = new PlaytestFeedback;

        $feedback->fill(['content' => $data->content]);

        $feedback->session_id = $session->getKey();
        $feedback->participant_id = $participant?->getKey();
        $feedback->rating = $data->rating?->value;
        $feedback->created_by = $recorder->id;

        $feedback->save();

        $feedback->setRelation('session', $session);
        $feedback->setRelation('participant', $participant);
        $feedback->setRelation('creator', $recorder);

        event(new FeedbackCreated(
            feedbackId: $feedback->id,
            sessionId: $session->getKey(),
            playtestId: $session->playtest_id,
            gameId: $session->playtest->game_id ?? '',
            participantId: $feedback->participant_id,
            rating: $feedback->rating,
            createdBy: $recorder->id,
        ));

        return $feedback;
    }
}
