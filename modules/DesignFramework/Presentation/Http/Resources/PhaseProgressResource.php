<?php

namespace Modules\DesignFramework\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DesignFramework\Application\DTOs\PhaseProgress;
use Modules\DesignFramework\Domain\ValueObjects\ProgressRatio;

/**
 * How far one game has got through one phase.
 *
 * Every ratio is sent as completed, total *and* percentage. The pair is what a screen should
 * usually show — "3 of 4" says how much work is left, where "75%" invites comparison between
 * games whose frameworks contain different amounts of content — and the percentage travels
 * anyway because a bar has to be filled to some width, and rounding it here keeps the
 * arithmetic out of TypeScript.
 *
 * `prompts` is reported and is not part of `overall`. A prompt has no right answer, and letting
 * one move a progress bar would reward typing over thinking.
 *
 * @mixin PhaseProgress
 */
class PhaseProgressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'phase_id' => $this->phaseId,
            'slug' => $this->slug,
            'name' => $this->name,
            'position' => $this->position,
            'criteria' => self::ratio($this->criteria),
            'practices' => self::ratio($this->practices),
            'checklist_items' => self::ratio($this->checklistItems),
            'prompts' => self::ratio($this->prompts),
            'overall' => self::ratio($this->overall),
            'percentage' => $this->percentage(),
            'is_complete' => $this->isComplete(),
            'is_empty' => $this->isEmpty(),
        ];
    }

    /**
     * Render a ratio the one way every progress figure is rendered.
     *
     * @return array{completed: int, total: int, percentage: int, is_complete: bool}
     */
    public static function ratio(ProgressRatio $ratio): array
    {
        return [
            'completed' => $ratio->completed,
            'total' => $ratio->total,
            'percentage' => $ratio->percentage(),
            'is_complete' => $ratio->isComplete(),
        ];
    }
}
