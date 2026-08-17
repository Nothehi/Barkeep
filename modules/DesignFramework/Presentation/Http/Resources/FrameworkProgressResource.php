<?php

namespace Modules\DesignFramework\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DesignFramework\Application\DTOs\FrameworkProgress;

/**
 * How far one game has got through its whole methodology.
 *
 * The version totals cover all of the edition's countable content, including anything filed
 * under no phase, while `phases` counts how many stages are finished and `phase_progress`
 * carries them one by one — which is what the progress screen draws as a stack of bars.
 *
 * Section 20 says this percentage is not for gamification yet, and nothing consumes it but a
 * bar. Whatever eventually wants to reward progress should read the events.
 *
 * @mixin FrameworkProgress
 */
class FrameworkProgressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'game_framework_id' => $this->gameFrameworkId,
            'framework_version_id' => $this->frameworkVersionId,
            'phases' => PhaseProgressResource::ratio($this->phases),
            'criteria' => PhaseProgressResource::ratio($this->criteria),
            'practices' => PhaseProgressResource::ratio($this->practices),
            'checklist_items' => PhaseProgressResource::ratio($this->checklistItems),
            'prompts' => PhaseProgressResource::ratio($this->prompts),
            'overall' => PhaseProgressResource::ratio($this->overall),
            'percentage' => $this->percentage(),
            'is_complete' => $this->isComplete(),
            'phase_progress' => PhaseProgressResource::collection($this->phaseProgress),
        ];
    }
}
