<?php

namespace Modules\DesignFramework\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;

/**
 * The representation of one stage of a methodology.
 *
 * `name` rather than `title`, matching the domain: a phase is named ("Core loop") while the
 * content filed under it is titled ("Does the core loop work?").
 *
 * The content is not nested. A phase page fetches the principles, criteria, practices, prompts
 * and checklists it wants; nesting all five here would make every phase list in the builder a
 * five-way eager load, and the builder shows ten phases at once.
 *
 * @mixin DesignPhaseDefinition
 */
class PhaseResource extends JsonResource
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
            'name' => $this->name,
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
