<?php

namespace Modules\DesignFramework\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DesignFramework\Domain\Models\DesignCriterion;

/**
 * One assessment question.

 * Carries the question and nothing about anybody's answer. A game's grade travels separately,
 * in {@see CriterionEvaluationResource}, which is the wire-level half of the separation
 * section 22 calls critical: a client that received a rating on a criterion would cache it and
 * show one studio's assessment to another.
 *
 * The phase is sent as an id rather than nested, because a phase page already knows which phase
 * it is and the builder renders the whole version's content grouped by phase from one flat list.
 * A null phase means the content applies across the methodology.
 *
 * @mixin DesignCriterion
 */
class CriterionResource extends JsonResource
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
            'framework_version_id' => $this->framework_version_id,
            'phase_id' => $this->phase_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'position' => $this->position,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
