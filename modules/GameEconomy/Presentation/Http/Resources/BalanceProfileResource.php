<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameDesign\Presentation\Http\Resources\GameVersionResource;
use Modules\GameEconomy\Domain\Enums\BalanceProfileStatus;
use Modules\GameEconomy\Domain\Models\BalanceProfile;
use Modules\GameEconomy\Infrastructure\Authorization\BalanceProfilePermissions;
use Modules\Identity\Domain\Models\User;
use Modules\Identity\Presentation\Http\Resources\UserResource;

/**
 * The representation of a single balance configuration.
 *
 * The design state it configures is rendered through GameDesign's own resource
 * rather than as a bare id, because a profile is hard to place without it —
 * "the economy as of v4" is how a designer locates it in the project's history.
 * GameDesign decides what is safe to publish about a version; this module does
 * not get a second opinion.
 *
 * Two things are included that a client could not work out for itself, and both
 * are here so that no rule ends up implemented twice:
 *
 * - `permissions`, from the policy, so the interface offers exactly what the
 *   server would allow;
 * - `available_transitions`, from the lifecycle matrix, so the buttons on a
 *   profile are the moves that profile can actually make.
 *
 * The configuration itself is absent. A profile accumulates resources, actions
 * and variables, and the screens that want them have their own endpoints; what
 * is here is the counts, which is what a header needs.
 *
 * @mixin BalanceProfile
 */
class BalanceProfileResource extends JsonResource
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
            'game_version_id' => $this->game_version_id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_active' => $this->isActive(),
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'version' => GameVersionResource::make($this->whenLoaded('version')),
            'resources_count' => $this->whenCounted('resources'),
            'flows_count' => $this->whenCounted('flows'),
            'actions_count' => $this->whenCounted('actions'),
            'variables_count' => $this->whenCounted('variables'),
            'scenarios_count' => $this->whenCounted('scenarios'),
            'snapshots_count' => $this->whenCounted('snapshots'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'permissions' => $this->permissionsFor($request),
            'available_transitions' => $this->availableTransitionsFor($request),
        ];
    }

    /**
     * Resolve what the caller may do with this configuration.
     *
     * @return array<string, bool>
     */
    private function permissionsFor(Request $request): array
    {
        $user = $request->user();

        return $user instanceof User
            ? app(BalanceProfilePermissions::class)->for($user, $this->resource)
            : BalanceProfilePermissions::none();
    }

    /**
     * The lifecycle moves this profile can make, that this caller may make.
     *
     * Derived from the matrix rather than restated, and filtered by the policy —
     * so a reader is offered nothing, and an archived profile is offered nothing
     * because it has nowhere to go. An empty list is a complete answer.
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
            BalanceProfileStatus::Active->value => 'activate',
            BalanceProfileStatus::Archived->value => 'archive',
        ];

        $moves = [];

        foreach ($from->transitions() as $target) {
            $ability = $abilities[$target->value] ?? null;

            if ($ability === null || ! $user->can($ability, $this->resource)) {
                continue;
            }

            $moves[] = [
                'status' => $target->value,
                'label' => $from->transitionLabelTo($target),
            ];
        }

        return $moves;
    }
}
