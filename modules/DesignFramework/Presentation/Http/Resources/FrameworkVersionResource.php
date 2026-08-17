<?php

namespace Modules\DesignFramework\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Infrastructure\Authorization\FrameworkPermissions;
use Modules\Identity\Domain\Models\User;

/**
 * The representation of one edition of a methodology.
 *
 * `label` is sent as well as the number because "v1" is what people read, and deriving it in
 * TypeScript would put a formatting decision in two places. `is_editable` is sent because it
 * is the single fact the whole builder is arranged around: everything is read-only once a
 * version is published, and asking the client to infer that from a status string is asking it
 * to reimplement the invariant.
 *
 * `adoptions_count` is included when loaded, because it is what makes archiving a version a
 * decision rather than a click — an author about to retire v1 should see that eleven studios
 * are working through it.
 *
 * @mixin FrameworkVersion
 */
class FrameworkVersionResource extends JsonResource
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
            'framework_id' => $this->framework_id,
            'version_number' => $this->version_number,
            'label' => $this->label(),
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_editable' => $this->isModifiable(),
            'is_adoptable' => $this->allowsAdoption(),
            'published_at' => $this->published_at?->toIso8601String(),
            'phases_count' => $this->whenCounted('phases'),
            'adoptions_count' => $this->whenCounted('adoptions'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'permissions' => $this->permissionsFor($request),
            'available_transitions' => $this->availableTransitionsFor($request),
        ];
    }

    /**
     * Resolve what the caller may do with this edition.
     *
     * @return array<string, bool>
     */
    private function permissionsFor(Request $request): array
    {
        $user = $request->user();

        return $user instanceof User
            ? app(FrameworkPermissions::class)->forVersion($user, $this->resource)
            : FrameworkPermissions::noneForVersion();
    }

    /**
     * The lifecycle moves this edition can make, that this caller may make.
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
            'publishVersion' => FrameworkStatus::Published,
            'archiveVersion' => FrameworkStatus::Archived,
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
