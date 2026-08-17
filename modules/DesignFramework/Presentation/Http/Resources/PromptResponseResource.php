<?php

namespace Modules\DesignFramework\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DesignFramework\Domain\Models\PromptResponse;
use Modules\Identity\Presentation\Http\Resources\UserResource;

/**
 * What a game's designers wrote in answer to one of the framework's questions.
 *
 * `was_revised` is sent because it changes how the answer reads: a paragraph somebody has come
 * back to and rewritten is a paragraph they are still thinking about.
 *
 * This is the one resource in the module carrying substantial free text a studio wrote about its
 * own design, which is why it only ever appears scoped to that studio's adoption.
 *
 * @mixin PromptResponse
 */
class PromptResponseResource extends JsonResource
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
            'prompt_id' => $this->prompt_id,
            'prompt' => PromptResource::make($this->whenLoaded('prompt')),
            'response' => $this->response,
            'was_revised' => $this->wasRevised(),
            'answered_by' => $this->answered_by,
            'author' => UserResource::make($this->whenLoaded('author')),
            'answered_at' => $this->answered_at->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
