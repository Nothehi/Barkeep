<?php

namespace Modules\Playtesting\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Identity\Presentation\Http\Resources\UserResource;
use Modules\Playtesting\Domain\Models\PlaytestObservation;

/**
 * The representation of something a designer noticed.
 *
 * `occurred_at` is sent alongside `observed_at` and is the field a timeline
 * should sort by: it falls back to when the observation was written down, so
 * notes typed up after the session still have somewhere to sit instead of
 * dropping out of the account on a null. Keeping both means a screen can still
 * tell "noticed at 20:14" from "written up later".
 *
 * The participant is rendered through this module's own resource rather than
 * as a bare id, because "player two never read the reference card" is only
 * useful if the reader knows who player two was.
 *
 * @mixin PlaytestObservation
 */
class ObservationResource extends JsonResource
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
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'content' => $this->content,
            'observed_at' => $this->observed_at?->toIso8601String(),
            'occurred_at' => $this->occurredAt()?->toIso8601String(),
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
