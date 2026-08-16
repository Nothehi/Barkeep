<?php

namespace Modules\Playtesting\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Playtesting\Domain\Models\Playtest;

/**
 * A playtest as it appears in a list.
 *
 * Smaller than {@see PlaytestResource} on purpose. A playtests screen renders
 * many at once and needs none of the per-playtest answers the full resource
 * computes: cards do not offer lifecycle actions, so they need neither the
 * permission map nor the transition list, and resolving both for every row
 * would be work done to be thrown away.
 *
 * The hypothesis is here and the objective is not, which is the one editorial
 * decision in this file. A list of playtests is scanned for "what were we
 * trying to find out?", and the hypothesis is the sharper answer — the
 * objective is usually a paragraph.
 *
 * @mixin Playtest
 */
class PlaytestSummaryResource extends JsonResource
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
            'title' => $this->title,
            'hypothesis' => $this->hypothesis,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'version_label' => $this->version?->label(),
            'planned_at' => $this->planned_at?->toIso8601String(),
            'sessions_count' => $this->whenCounted('sessions'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
