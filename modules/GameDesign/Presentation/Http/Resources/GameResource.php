<?php

namespace Modules\GameDesign\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Domain\Models\Game;
use Modules\GameDesign\Infrastructure\Authorization\GamePermissions;
use Modules\Identity\Domain\Models\User;

/**
 * The representation of a single game.
 *
 * Two things are included that a client could not work out for itself, and
 * both are here so that no rule ends up implemented twice:
 *
 * - `permissions`, from the policy, so the interface offers exactly what the
 *   server would allow;
 * - `available_transitions`, from the lifecycle matrix, so the buttons on a
 *   game are the moves that game can actually make. The client renders the
 *   list; it does not know the matrix.
 *
 * Versions are absent. A game will accumulate many, and the screens that want
 * them have their own endpoint.
 *
 * @mixin Game
 */
class GameResource extends JsonResource
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
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'design_phase' => $this->design_phase->value,
            'design_phase_label' => $this->design_phase->label(),
            'design_phase_position' => $this->design_phase->position(),
            'design_phase_count' => DesignPhase::count(),
            'created_by' => $this->created_by,
            'versions_count' => $this->whenCounted('versions'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'permissions' => $this->permissionsFor($request),
            'available_transitions' => $this->availableTransitionsFor($request),
        ];
    }

    /**
     * Resolve what the caller may do with this game.
     *
     * @return array<string, bool>
     */
    private function permissionsFor(Request $request): array
    {
        $user = $request->user();

        return $user instanceof User
            ? app(GamePermissions::class)->for($user, $this->resource)
            : GamePermissions::none();
    }

    /**
     * The lifecycle moves this game can make, that this caller may make.
     *
     * Archived is filtered out because archival has its own action and its
     * own ability, and an irreversible move does not belong in the same row
     * of buttons as "put on hold".
     *
     * An empty list is a complete answer: an archived game has nowhere to go,
     * and a reader who cannot change the game has no moves to offer.
     *
     * @return list<array{status: string, label: string}>
     */
    private function availableTransitionsFor(Request $request): array
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->can('changeStatus', $this->resource)) {
            return [];
        }

        $from = $this->status;

        return array_values(array_map(
            fn (GameStatus $target): array => [
                'status' => $target->value,
                'label' => $from->transitionLabelTo($target),
            ],
            array_filter(
                $from->transitions(),
                fn (GameStatus $target): bool => $target !== GameStatus::Archived,
            ),
        ));
    }
}
