<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameEconomy\Domain\Models\BalanceSnapshot;
use Modules\Identity\Presentation\Http\Resources\UserResource;

/**
 * The representation of one frozen configuration.
 *
 * The payload itself is deliberately absent. A snapshot of a real economy runs
 * to hundreds of kilobytes, the snapshots list draws four of them at once, and
 * nothing on that screen reads the contents — what a reader wants is "v1.2, Aug
 * 18, 8 resources" and a button to compare.
 *
 * The comparison endpoint is where the payloads are read, and it returns the
 * difference rather than the two sides.
 *
 * There is no `updated_at`, because there is no update. A snapshot is written
 * once and refuses every subsequent write at the model level.
 *
 * @mixin BalanceSnapshot
 */
class BalanceSnapshotResource extends JsonResource
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
            'balance_profile_id' => $this->balance_profile_id,
            'name' => $this->name,
            'description' => $this->description,
            'tally' => $this->tally(),
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
