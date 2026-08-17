<?php

namespace Modules\DesignFramework\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DesignFramework\Domain\Models\DesignPractice;

/**
 * One activity a designer is asked to carry out.

 * Instructions are sent alongside the description because the phase page shows the summary and
 * the practice card expands to the detail, and a second request for the body of something
 * already on screen would be a round trip for nothing.
 *
 * The phase is sent as an id rather than nested, because a phase page already knows which phase
 * it is and the builder renders the whole version's content grouped by phase from one flat list.
 * A null phase means the content applies across the methodology.
 *
 * @mixin DesignPractice
 */
class PracticeResource extends JsonResource
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
            'instructions' => $this->instructions,
            'position' => $this->position,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
