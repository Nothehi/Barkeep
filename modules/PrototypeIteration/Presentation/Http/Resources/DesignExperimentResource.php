<?php

namespace Modules\PrototypeIteration\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Identity\Domain\Models\User;
use Modules\Identity\Presentation\Http\Resources\UserResource;
use Modules\PrototypeIteration\Domain\Enums\ExperimentStatus;
use Modules\PrototypeIteration\Domain\Models\DesignExperiment;

/**
 * The representation of one focused attempt to answer a design question.
 *
 * The two halves are sent as two halves, in the order they were written. That is not just
 * tidiness in the payload: the interface renders the before half as a plan and the after half
 * as a finding, and a reader has to be able to see which is which. An experiment whose
 * prediction and result were presented as one block of prose would lose the only thing that
 * makes a prediction worth anything.
 *
 * `available_transitions` comes from the lifecycle matrix rather than being derived from the
 * status on the client, so the buttons on an experiment are the moves it can actually make.
 * Note that the iteration completing does *not* remove them: an experiment still running when
 * its cycle closed is refused by the guard, and the empty transition list is what the client
 * renders instead of a stale "Complete" button.
 *
 * @mixin DesignExperiment
 */
class DesignExperimentResource extends JsonResource
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

            /*
             * Written before the experiment ran, and frozen once it is answered.
             */
            'question' => $this->question,
            'hypothesis' => $this->hypothesis,
            'method' => $this->method,
            'expected_result' => $this->expected_result,

            /*
             * Written after it ran. `conclusion` may be null against a populated
             * `actual_result`, which is a real state rather than an incomplete one: the studio
             * has seen what happened and not yet decided what it means.
             */
            'actual_result' => $this->actual_result,
            'conclusion' => $this->conclusion,

            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'available_transitions' => $this->availableTransitionsFor($request),
        ];
    }

    /**
     * The lifecycle moves this experiment can make, that this caller may make.
     *
     * Every move is gated on the same ability — recording work in the cycle — because an
     * experiment has no permissions of its own; what decides whether it may move is whether the
     * iteration around it is still open. The matrix decides which moves exist, the ability
     * decides whether to offer any of them.
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
            fn (ExperimentStatus $target): array => [
                'status' => $target->value,
                'label' => $from->transitionLabelTo($target),
            ],
            $from->transitions(),
        );
    }
}
