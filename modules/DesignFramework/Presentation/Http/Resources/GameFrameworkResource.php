<?php

namespace Modules\DesignFramework\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DesignFramework\Domain\Enums\GameFrameworkStatus;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Infrastructure\Authorization\GameFrameworkPermissions;
use Modules\Identity\Domain\Models\User;

/**
 * The representation of a game's adoption of a methodology.
 *
 * The version is nested rather than sent as an id, because an adoption is unreadable without
 * knowing what was adopted — "this game follows v1 of the Board Game Design Framework" is the
 * whole point of the record, and it has to stay true on screen after v2 ships.
 *
 * The framework travels inside the version, so a screen can name the methodology without a
 * second request.
 *
 * `permissions` comes from the policy, so the interface offers exactly what the server would
 * allow. `canRecordProgress` is the one that matters: it covers evaluations, completions, ticks
 * and answers together, because the policy grants them together.
 *
 * @mixin GameFramework
 */
class GameFrameworkResource extends JsonResource
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
            'framework_version_id' => $this->framework_version_id,
            'version' => FrameworkVersionResource::make($this->whenLoaded('version')),
            'framework' => FrameworkResource::make($this->whenLoaded('version', fn () => $this->version?->framework)),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'accepts_progress' => $this->acceptsProgress(),
            'started_at' => $this->started_at->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'adopted_by' => $this->adopted_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'permissions' => $this->permissionsFor($request),
            'available_transitions' => $this->availableTransitionsFor($request),
        ];
    }

    /**
     * Resolve what the caller may do with this adoption.
     *
     * @return array<string, bool>
     */
    private function permissionsFor(Request $request): array
    {
        $user = $request->user();

        return $user instanceof User
            ? app(GameFrameworkPermissions::class)->for($user, $this->resource)
            : GameFrameworkPermissions::none();
    }

    /**
     * The lifecycle moves this adoption can make, that this caller may make.
     *
     * The wording depends on both ends — reaching Active from Paused is resuming, not "making
     * active" — which is why the label comes from the enum rather than from the target alone.
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
        $abilities = [
            'resume' => GameFrameworkStatus::Active,
            'pause' => GameFrameworkStatus::Paused,
            'complete' => GameFrameworkStatus::Completed,
        ];

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
