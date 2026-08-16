<?php

namespace Modules\Playtesting\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Playtesting\Application\DTOs\PlaytestSummary;

/**
 * What a playtest has produced, counted on read.
 *
 * Wraps the read model rather than a model, which is why it is the one
 * resource here with no `id`: it describes a playtest rather than being one.
 *
 * Every null is meaningful and none of them is a zero. A playtest with no
 * rated feedback has no average rating, and reporting 0.0 would put it at the
 * bottom of any ordering it appeared in; a playtest whose sessions never ran
 * has no average duration, which is different from an average of nothing.
 *
 * Durations are sent as seconds *and* as a label. The label is the wording the
 * domain uses — "1h 15m" — so a screen renders it rather than inventing its
 * own formatting, while the seconds stay available for anything that needs to
 * compute.
 *
 * @mixin PlaytestSummary
 */
class PlaytestMetricsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PlaytestSummary $summary */
        $summary = $this->resource;

        return [
            'playtest_id' => $summary->playtest->id,
            'session_count' => $summary->sessionCount,
            'completed_session_count' => $summary->completedSessionCount,
            'cancelled_session_count' => $summary->cancelledSessionCount,
            'participant_count' => $summary->participantCount,
            'player_count' => $summary->playerCount,
            'observation_count' => $summary->observationCount,
            'feedback_count' => $summary->feedbackCount,
            'rated_feedback_count' => $summary->ratedFeedbackCount,
            'average_feedback_rating' => $summary->averageRating,
            'total_duration_seconds' => $summary->totalDuration?->seconds,
            'total_duration_label' => $summary->totalDuration?->label(),
            'average_session_duration_seconds' => $summary->averageSessionDuration?->seconds,
            'average_session_duration_label' => $summary->averageSessionDuration?->label(),
            'has_evidence' => $summary->hasEvidence(),
        ];
    }
}
