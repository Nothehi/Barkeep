<?php

namespace Modules\DesignFramework\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Infrastructure\Authorization\FrameworkPermissions;
use Modules\Identity\Domain\Models\User;

/**
 * The representation of a single methodology.
 *
 * Two things are included that a client could not work out for itself, and both are here so
 * that no rule ends up implemented twice:
 *
 * - `permissions`, from the policy, so the interface offers exactly what the server would
 *   allow;
 * - `available_transitions`, from the lifecycle matrix, so the buttons on a framework are the
 *   moves that framework can actually make.
 *
 * The latest version comes along when it is loaded, because a framework card is unreadable
 * without it — "Board Game Design Framework" with no edition tells a designer nothing about
 * whether there is anything to adopt.
 *
 * The creator is deliberately absent. A methodology is the platform's rather than a person's,
 * and putting an author's name on it would invite the reading that it is somebody's opinion.
 *
 * @mixin Framework
 */
class FrameworkResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'latest_version' => FrameworkVersionResource::make($this->whenLoaded('latestVersion')),
            'versions_count' => $this->whenCounted('versions'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'permissions' => $this->permissionsFor($request),
            'available_transitions' => $this->availableTransitionsFor($request),
        ];
    }

    /**
     * Resolve what the caller may do with this framework.
     *
     * @return array<string, bool>
     */
    private function permissionsFor(Request $request): array
    {
        $user = $request->user();

        return $user instanceof User
            ? app(FrameworkPermissions::class)->for($user, $this->resource)
            : FrameworkPermissions::none();
    }

    /**
     * The lifecycle moves this framework can make, that this caller may make.
     *
     * An empty list is a complete answer: an archived framework has nowhere to go, and a
     * reader who does not administer frameworks has no moves to offer.
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
        $abilities = ['publish' => FrameworkStatus::Published, 'archive' => FrameworkStatus::Archived];

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
