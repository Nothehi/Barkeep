<?php

namespace Modules\Playtesting\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Identity\Presentation\Http\Resources\UserResource;
use Modules\Playtesting\Domain\Models\PlaytestFeedback;
use Modules\Playtesting\Domain\ValueObjects\FeedbackRating;

/**
 * The representation of something a participant said.
 *
 * Two people appear and they are usually different. `participant` is who said
 * it — often absent, because anonymous feedback is often the honest kind — and
 * `creator` is whoever typed it in, which is normally the facilitator.
 * Collapsing the two would turn "the facilitator wrote this down" into "the
 * facilitator said this".
 *
 * The scale is published alongside the rating so a screen can draw five stars
 * without knowing that there are five. A null rating is not a zero: the
 * participant did not put a number on their comment, which is different from
 * scoring the game badly.
 *
 * @mixin PlaytestFeedback
 */
class FeedbackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'session_id' => $this->session_id,
            'participant_id' => $this->participant_id,
            'participant' => ParticipantResource::make($this->whenLoaded('participant')),
            'content' => $this->content,
            'rating' => $this->rating,
            'rating_label' => $this->rating()?->label(),
            'rating_max' => FeedbackRating::MAX,
            'is_anonymous' => $this->isAnonymous(),
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
