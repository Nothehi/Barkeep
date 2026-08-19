<?php

namespace Modules\PrototypeIteration\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameDesign\Presentation\Http\Resources\GameVersionResource;
use Modules\Identity\Domain\Models\User;
use Modules\Identity\Presentation\Http\Resources\UserResource;
use Modules\PrototypeIteration\Domain\Enums\IterationStatus;
use Modules\PrototypeIteration\Domain\Models\Iteration;
use Modules\PrototypeIteration\Infrastructure\Authorization\IterationPermissions;

/**
 * The representation of a single design cycle.
 *
 * Both versions are rendered as resources rather than as bare ids, because an iteration is
 * unreadable without them: "v7 of the design, v4 of the combat prototype" is what places the
 * cycle in the project's history, and it is the pairing the module exists to keep straight.
 * GameDesign decides what is safe to publish about its version; this module does not get a
 * second opinion.
 *
 * Two things are included that a client could not work out for itself, and both are here so
 * that no rule ends up implemented twice:
 *
 * - `permissions`, from the policy, so the busiest screen in the application offers exactly
 *   what the server would allow;
 * - `available_transitions`, from the lifecycle matrix, so the buttons on a cycle are the
 *   moves that cycle can actually make.
 *
 * The changes, experiments, decisions and playtests are absent. Each has its own endpoint and
 * its own resource, and a cycle late in a project accumulates enough of all four that shipping
 * them with every read of the iteration would make the header slow to draw. What is here are
 * the counts.
 *
 * @mixin Iteration
 */
class IterationResource extends JsonResource
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
            'prototype_version_id' => $this->prototype_version_id,
            'title' => $this->title,
            'objective' => $this->objective,
            'hypothesis' => $this->hypothesis,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'outcome' => $this->outcome?->value,
            'outcome_label' => $this->outcome?->label(),
            'summary' => $this->summary,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'version' => GameVersionResource::make($this->whenLoaded('version')),
            'prototype_version' => PrototypeVersionResource::make($this->whenLoaded('prototypeVersion')),
            'changes_count' => $this->whenCounted('changes'),
            'experiments_count' => $this->whenCounted('experiments'),
            'decisions_count' => $this->whenCounted('decisions'),
            'playtests_count' => $this->whenCounted('playtestLinks'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'permissions' => $this->permissionsFor($request),
            'available_transitions' => $this->availableTransitionsFor($request),
        ];
    }

    /**
     * Resolve what the caller may do with this cycle.
     *
     * @return array<string, bool>
     */
    private function permissionsFor(Request $request): array
    {
        $user = $request->user();

        return $user instanceof User
            ? app(IterationPermissions::class)->for($user, $this->resource)
            : IterationPermissions::none();
    }

    /**
     * The lifecycle moves this cycle can make, that this caller may make.
     *
     * All three are real buttons here, which is where an iteration differs from a playtest. A
     * playtest starts itself when its first session begins, because the system can see that
     * happen; a design cycle has no such signal — recording a change does not mean the work has
     * begun — so starting one is something a designer says.
     *
     * An empty list is a complete answer: a completed cycle has nowhere to go, and a reader who
     * cannot change it has no moves to offer.
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
            'start' => IterationStatus::InProgress,
            'complete' => IterationStatus::Completed,
            'cancel' => IterationStatus::Cancelled,
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
