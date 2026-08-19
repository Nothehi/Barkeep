<?php

namespace Modules\PrototypeIteration\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\PrototypeIteration\Domain\Models\Iteration;

/**
 * A design cycle as it appears in a list.
 *
 * Smaller than {@see IterationResource} on purpose: cards do not offer lifecycle actions, so
 * the server does not compute permissions or transitions for them. The iterations screen is
 * the longest list in the module — a project two years in has dozens of cycles — and asking
 * the gate eight times per row for answers nothing on the card uses would dominate the
 * response.
 *
 * The objective is here and the hypothesis is not, which is the opposite of the choice
 * Playtesting makes for its cards. A playtest list is scanned for "what were we trying to find
 * out?", where a hypothesis is the sharper line; an iterations list is scanned for "what were
 * we trying to fix?", and that is the objective. The four counts beside it are what tell a
 * reader how substantial the cycle was without opening it.
 *
 * Both versions are flattened to labels. A card needs "v7 · Core Combat v4" and nothing else
 * about either.
 *
 * @mixin Iteration
 */
class IterationCardResource extends JsonResource
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
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'outcome' => $this->outcome?->value,
            'outcome_label' => $this->outcome?->label(),
            'version_label' => $this->whenLoaded('version', fn (): ?string => $this->version?->label()),
            'prototype_version_label' => $this->whenLoaded(
                'prototypeVersion',
                fn (): ?string => $this->prototypeVersion?->label(),
            ),
            'prototype_name' => $this->whenLoaded(
                'prototypeVersion',
                fn (): ?string => $this->prototypeVersion?->prototype?->name,
            ),
            'changes_count' => $this->whenCounted('changes'),
            'experiments_count' => $this->whenCounted('experiments'),
            'decisions_count' => $this->whenCounted('decisions'),
            'playtests_count' => $this->whenCounted('playtestLinks'),
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
