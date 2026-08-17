<?php

namespace Modules\DesignFramework\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DesignFramework\Domain\Models\DesignPrompt;

/**
 * One thinking question.

 * `title` is the label a list shows; `prompt` is the question that sits above the textarea. Both
 * travel because they are read at different moments.
 *
 * The phase is sent as an id rather than nested, because a phase page already knows which phase
 * it is and the builder renders the whole version's content grouped by phase from one flat list.
 * A null phase means the content applies across the methodology.
 *
 * @mixin DesignPrompt
 */
class PromptResource extends JsonResource
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
            'prompt' => $this->prompt,
            'position' => $this->position,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
