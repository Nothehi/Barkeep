<?php

namespace Modules\DesignFramework\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DesignFramework\Domain\Models\PracticeCompletion;
use Modules\Identity\Presentation\Http\Resources\UserResource;

/**
 * A record that a game carried out one of its framework's activities.
 *
 * The existence of this object is the completion, so there is no `completed` field to disagree
 * with it. A practice with no completion resource is not done.
 *
 * @mixin PracticeCompletion
 */
class PracticeCompletionResource extends JsonResource
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
            'practice_id' => $this->practice_id,
            'practice' => PracticeResource::make($this->whenLoaded('practice')),
            'notes' => $this->notes,
            'completed_by' => $this->completed_by,
            'completer' => UserResource::make($this->whenLoaded('completer')),
            'completed_at' => $this->completed_at->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
