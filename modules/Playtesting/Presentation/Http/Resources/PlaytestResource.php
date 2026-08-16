<?php

namespace Modules\Playtesting\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameDesign\Presentation\Http\Resources\GameVersionResource;
use Modules\Identity\Domain\Models\User;
use Modules\Identity\Presentation\Http\Resources\UserResource;
use Modules\Playtesting\Domain\Enums\PlaytestStatus;
use Modules\Playtesting\Domain\Models\Playtest;
use Modules\Playtesting\Infrastructure\Authorization\PlaytestPermissions;

/**
 * The representation of a single playtest.
 *
 * The version is rendered through GameDesign's own resource rather than as a
 * bare id, because a playtest is unreadable without knowing what was on the
 * table — "v3" is the whole point of the record. GameDesign decides what is
 * safe to publish about one; this module does not get a second opinion.
 *
 * Two things are included that a client could not work out for itself, and
 * both are here so that no rule ends up implemented twice:
 *
 * - `permissions`, from the policy, so the interface offers exactly what the
 *   server would allow;
 * - `available_transitions`, from the lifecycle matrix, so the buttons on a
 *   playtest are the moves that playtest can actually make.
 *
 * Sessions are absent. A playtest accumulates them and the screens that want
 * them have their own endpoint.
 *
 * @mixin Playtest
 */
class PlaytestResource extends JsonResource
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
            'game_id' => $this->game_id,
            'game_version_id' => $this->game_version_id,
            'title' => $this->title,
            'objective' => $this->objective,
            'hypothesis' => $this->hypothesis,
            'conclusion' => $this->conclusion,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'planned_at' => $this->planned_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'version' => GameVersionResource::make($this->whenLoaded('version')),
            'sessions_count' => $this->whenCounted('sessions'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'permissions' => $this->permissionsFor($request),
            'available_transitions' => $this->availableTransitionsFor($request),
        ];
    }

    /**
     * Resolve what the caller may do with this playtest.
     *
     * @return array<string, bool>
     */
    private function permissionsFor(Request $request): array
    {
        $user = $request->user();

        return $user instanceof User
            ? app(PlaytestPermissions::class)->for($user, $this->resource)
            : PlaytestPermissions::none();
    }

    /**
     * The lifecycle moves this playtest can make, that this caller may make.
     *
     * "In progress" is filtered out even though the matrix allows it, because
     * it is not a button. A playtest becomes in progress when its first
     * session starts — the system can see that for itself, and asking a
     * designer to announce it separately would be asking them to maintain a
     * status by hand.
     *
     * An empty list is a complete answer: a completed playtest has nowhere to
     * go, and a reader who cannot change it has no moves to offer.
     *
     * @return list<array{status: string, label: string}>
     */
    private function availableTransitionsFor(Request $request): array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return [];
        }

        $from = $this->status;
        $abilities = ['complete' => PlaytestStatus::Completed, 'cancel' => PlaytestStatus::Cancelled];

        $moves = [];

        foreach ($abilities as $ability => $target) {
            if ($from->canTransitionTo($target) && $user->can($ability, $this->resource)) {
                $moves[] = [
                    'status' => $target->value,
                    'label' => $from->transitionLabelTo($target),
                ];
            }
        }

        return $moves;
    }
}
