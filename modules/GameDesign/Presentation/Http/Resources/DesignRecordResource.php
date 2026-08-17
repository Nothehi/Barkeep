<?php

namespace Modules\GameDesign\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameDesign\Domain\Models\DesignRecord;

/**
 * The representation of what has been decided about a game's design.
 *
 * The raw numbers travel alongside a rendered label for each range, because the
 * two have different jobs: a form needs `player_count_min` to put in a box, and
 * a heading needs "2 to 4 players" — and deriving the second in TypeScript would
 * put a formatting decision in two places, one of which would eventually say
 * "90 min" where the other said "1 h 30 min".
 *
 * `is_empty` is sent so a screen can tell "nothing decided yet" from "decided to
 * leave blank" without comparing thirteen fields against null.
 *
 * The mechanics are nested in full rather than as ids, because a design record
 * showing three uuids is unreadable and the alternative is every consumer
 * fetching the vocabulary to render a list of three words.
 *
 * @mixin DesignRecord
 */
class DesignRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $playerCount = $this->playerCount();
        $playTime = $this->playTime();

        return [
            'id' => $this->id,
            'game_id' => $this->game_id,

            'pitch' => $this->pitch,

            'player_count_min' => $this->player_count_min,
            'player_count_max' => $this->player_count_max,
            'player_count_label' => $playerCount?->label(),

            'play_time_min' => $this->play_time_min,
            'play_time_max' => $this->play_time_max,
            'play_time_label' => $playTime?->label(),

            'target_age_min' => $this->target_age_min,

            'complexity' => $this->complexity?->value,
            'complexity_label' => $this->complexity?->label(),
            'complexity_description' => $this->complexity?->description(),

            'audience' => $this->audience,

            'core_action' => $this->core_action,
            'core_cost' => $this->core_cost,
            'core_reward' => $this->core_reward,
            'win_condition' => $this->win_condition,
            'failure_condition' => $this->failure_condition,
            'has_complete_core_loop' => $this->hasCompleteCoreLoop(),

            'mechanics' => MechanicResource::collection($this->whenLoaded('mechanics')),

            'is_empty' => $this->isEmpty(),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
