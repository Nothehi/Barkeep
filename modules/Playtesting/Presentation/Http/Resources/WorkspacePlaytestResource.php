<?php

namespace Modules\Playtesting\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Playtesting\Domain\Models\Playtest;

/**
 * A playtest as it appears on a list that spans a studio's whole workspace.
 *
 * Everything {@see PlaytestSummaryResource} sends, plus the game it belongs
 * to. That addition is the entire reason this resource exists: a title like
 * "Does the two-player opening still stall?" identifies an investigation
 * perfectly on a game's own screen and identifies nothing at all on a list
 * that mixes four projects together. The game is also what makes the row
 * clickable, since every playtest URL is nested under its game's address.
 *
 * The summary is delegated to rather than restated so that a field added to a
 * playtest row appears here too, instead of this being a copy that quietly
 * falls behind.
 *
 * @mixin Playtest
 */
class WorkspacePlaytestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * Resolved rather than converted with `toArray()`, because
             * resolving is what discards the counts and relations this
             * playtest was not loaded with — spreading the raw array would
             * leak Laravel's own "missing" placeholder into the response.
             */
            ...PlaytestSummaryResource::make($this->resource)->resolve($request),

            'game' => [
                'name' => $this->game?->name,
                'slug' => $this->game?->slug,
            ],
        ];
    }
}
