<?php

namespace Modules\Playtesting\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Identity\Domain\Models\User;
use Modules\Identity\Presentation\Http\Resources\UserResource;
use Modules\Playtesting\Domain\Models\PlaytestSession;
use Modules\Playtesting\Infrastructure\Authorization\SessionPermissions;

/**
 * The representation of one sitting of a playtest.
 *
 * The duration is computed rather than stored, and it is sent as seconds *and*
 * as the domain's own wording so a screen renders "1h 15m" instead of
 * inventing its own formatting. A running session has none — which is
 * different from zero, and is why the client draws a live counter from
 * `started_at` instead of reading this.
 *
 * The counts appear only when the caller asked for them. A session list wants
 * them for every row; the session screen has the actual records and does not
 * need them counted separately.
 *
 * @mixin PlaytestSession
 */
class PlaytestSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $duration = $this->duration();

        return [
            'id' => $this->id,
            'playtest_id' => $this->playtest_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'planned_at' => $this->planned_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'duration_seconds' => $duration?->seconds,
            'duration_label' => $duration?->label(),
            'location' => $this->location,
            'notes' => $this->notes,
            'outcome' => $this->outcome,
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'participants_count' => $this->whenCounted('participants'),
            'observations_count' => $this->whenCounted('observations'),
            'feedback_count' => $this->whenCounted('feedback'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'permissions' => $this->permissionsFor($request),
        ];
    }

    /**
     * Resolve what the caller may do with this session.
     *
     * @return array<string, bool>
     */
    private function permissionsFor(Request $request): array
    {
        $user = $request->user();

        return $user instanceof User
            ? app(SessionPermissions::class)->for($user, $this->resource)
            : SessionPermissions::none();
    }
}
