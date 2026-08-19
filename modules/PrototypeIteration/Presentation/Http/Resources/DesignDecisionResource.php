<?php

namespace Modules\PrototypeIteration\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Identity\Domain\Models\User;
use Modules\Identity\Presentation\Http\Resources\UserResource;
use Modules\PrototypeIteration\Domain\Enums\DecisionStatus;
use Modules\PrototypeIteration\Domain\Models\DesignDecision;

/**
 * The representation of a design decision.
 *
 * The record the module builds towards, so everything about it that a reader needs is here:
 * what was decided, why, where it stands, and who settled it. `decider` is rendered through
 * Identity's own resource because "accepted by" with an account id in it is unreadable, and the
 * attribution is half of what makes a decision citable later.
 *
 * The citations are included when loaded but are *not* resolved here. What this ships is the
 * stored rows — type, reference, description; turning them into readable exhibits means reading
 * the cited words live from Playtesting, which is a query's job rather than a resource's. See
 * `GetDecisionEvidence`, and `DecisionEvidenceResource` for the resolved shape.
 *
 * `available_transitions` is the strictest such list in the module. An accepted decision has
 * none, because reversal is a new decision rather than an edit — and the interface therefore
 * cannot offer a "Reject" button on something already agreed, whatever a stale page thinks.
 *
 * @mixin DesignDecision
 */
class DesignDecisionResource extends JsonResource
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
            'iteration_id' => $this->iteration_id,
            'title' => $this->title,
            'decision' => $this->decision,
            'reason' => $this->reason,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_settled' => $this->isSettled(),
            'decided_by' => $this->decided_by,
            'decider' => UserResource::make($this->whenLoaded('decider')),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'evidence_count' => $this->whenCounted('evidence'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'available_transitions' => $this->availableTransitionsFor($request),
        ];
    }

    /**
     * The moves this decision can make, that this caller may make.
     *
     * @return list<array{status: string, label: string}>
     */
    private function availableTransitionsFor(Request $request): array
    {
        $user = $request->user();
        $iteration = $this->resource->iteration;

        if (! $user instanceof User || $iteration === null || ! $user->can('recordWork', $iteration)) {
            return [];
        }

        $from = $this->status;

        return array_map(
            fn (DecisionStatus $target): array => [
                'status' => $target->value,
                'label' => $from->transitionLabelTo($target),
            ],
            $from->transitions(),
        );
    }
}
