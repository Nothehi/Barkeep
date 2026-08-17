<?php

namespace Modules\DesignFramework\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DesignFramework\Domain\Models\CriterionEvaluation;
use Modules\Identity\Presentation\Http\Resources\UserResource;

/**
 * One game's assessment of itself against one criterion.
 *
 * `status` is the grade and `is_evaluated` is whether one was given, because "not evaluated" is
 * not a low score and a client that treated the two the same would tell a designer their
 * untouched game was failing.
 *
 * The criterion is nested when loaded, so a list of assessments reads as a list of questions and
 * answers rather than of ids.
 *
 * @mixin CriterionEvaluation
 */
class CriterionEvaluationResource extends JsonResource
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
            'game_framework_id' => $this->game_framework_id,
            'criterion_id' => $this->criterion_id,
            'criterion' => CriterionResource::make($this->whenLoaded('criterion')),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_evaluated' => $this->isEvaluated(),
            'is_satisfactory' => $this->isSatisfactory(),
            'notes' => $this->notes,
            'evaluated_by' => $this->evaluated_by,
            'evaluator' => UserResource::make($this->whenLoaded('evaluator')),
            'evaluated_at' => $this->evaluated_at->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
