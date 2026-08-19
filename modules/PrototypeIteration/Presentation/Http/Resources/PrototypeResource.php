<?php

namespace Modules\PrototypeIteration\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameDesign\Presentation\Http\Resources\GameVersionResource;
use Modules\Identity\Domain\Models\User;
use Modules\Identity\Presentation\Http\Resources\UserResource;
use Modules\PrototypeIteration\Domain\Enums\PrototypeStatus;
use Modules\PrototypeIteration\Domain\Models\Prototype;
use Modules\PrototypeIteration\Infrastructure\Authorization\PrototypePermissions;

/**
 * The representation of a single prototype.
 *
 * The design version it was built from is rendered through GameDesign's own resource rather
 * than as a bare id, because a prototype is hard to place without it — "built from v4" is how a
 * designer locates it in the project's history. GameDesign decides what is safe to publish
 * about one; this module does not get a second opinion.
 *
 * Two things are included that a client could not work out for itself, and both are here so
 * that no rule ends up implemented twice:
 *
 * - `permissions`, from the policy, so the interface offers exactly what the server would
 *   allow;
 * - `available_transitions`, from the lifecycle matrix, so the buttons on a prototype are the
 *   moves that prototype can actually make.
 *
 * The versions themselves are absent. A prototype accumulates them and the screens that want
 * them have their own endpoint; what is here is the count, which is what a header needs.
 *
 * @mixin Prototype
 */
class PrototypeResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'version' => GameVersionResource::make($this->whenLoaded('version')),
            'versions_count' => $this->whenCounted('versions'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'permissions' => $this->permissionsFor($request),
            'available_transitions' => $this->availableTransitionsFor($request),
        ];
    }

    /**
     * Resolve what the caller may do with this prototype.
     *
     * @return array<string, bool>
     */
    private function permissionsFor(Request $request): array
    {
        $user = $request->user();

        return $user instanceof User
            ? app(PrototypePermissions::class)->for($user, $this->resource)
            : PrototypePermissions::none();
    }

    /**
     * The lifecycle moves this prototype can make, that this caller may make.
     *
     * Only archival is offered, even though the matrix also allows draft → active. Activating a
     * prototype is not a button somebody presses: a prototype becomes active when its first
     * version is cut, which the system can see for itself, and asking a designer to announce it
     * separately would be asking them to maintain a status by hand.
     *
     * An empty list is a complete answer: an archived prototype has nowhere to go, and a reader
     * who cannot change it has no moves to offer.
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

        if (! $from->canTransitionTo(PrototypeStatus::Archived) || ! $user->can('archive', $this->resource)) {
            return [];
        }

        return [[
            'status' => PrototypeStatus::Archived->value,
            'label' => $from->transitionLabelTo(PrototypeStatus::Archived),
        ]];
    }
}
