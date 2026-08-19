<?php

namespace Modules\PrototypeIteration\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\PrototypeIteration\Domain\ValueObjects\PlaytestReference;

/**
 * The representation of a playtest an iteration was tested through.
 *
 * Wraps this module's own reference value object rather than a Playtest, which is the boundary
 * showing up in the payload. Every figure here was read from Playtesting at the moment of the
 * request through the adapter, so the counts on an iteration screen are the counts the
 * playtest's own screen would show — not a cached copy that starts disagreeing the first time
 * somebody adds a session.
 *
 * `link_id` and `playtest_id` are both present because the interface needs both: the playtest id
 * to link into Playtesting, and the link id to detach the association without naming the
 * playtest at all. That second address is what lets the detach route belong entirely to this
 * module.
 *
 * `duration_seconds` is a number rather than a formatted string, because how long a playtest ran
 * is said differently in different languages and this application pluralises on the client.
 *
 * `is_available` false is a first-class state, not an error: a reader who can see the iteration
 * through one grant and the playtest through none gets a row saying so, which beats a silently
 * shorter list that reads as "this cycle was tested against nothing".
 *
 * @mixin PlaytestReference
 */
class PlaytestReferenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'link_id' => $this->linkId,
            'playtest_id' => $this->playtestId,
            'title' => $this->title,
            'status' => $this->status,
            'status_label' => $this->statusLabel,
            'attached_at' => $this->attachedAt?->format(DATE_ATOM),
            'sessions_count' => $this->sessionCount,
            'participants_count' => $this->participantCount,
            'observations_count' => $this->observationCount,
            'feedback_count' => $this->feedbackCount,
            'duration_seconds' => $this->totalDurationSeconds,
            'is_available' => $this->isAvailable(),
            'has_evidence' => $this->hasEvidence(),
        ];
    }
}
