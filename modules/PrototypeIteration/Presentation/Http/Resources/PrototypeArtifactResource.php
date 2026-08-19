<?php

namespace Modules\PrototypeIteration\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Identity\Presentation\Http\Resources\UserResource;
use Modules\PrototypeIteration\Domain\Models\PrototypeArtifact;

/**
 * The representation of a file attached to a prototype state.
 *
 * What is conspicuously absent is a URL. Artifacts are private — a studio's unreleased card art
 * is exactly the thing that must not leak by being guessable — so there is no public link and no
 * signed one either. The interface builds a download address from the ids, and that route
 * authorizes before it streams a byte.
 *
 * `storage_reference` is absent for the same reason at a lower level: where a file sits on which
 * disk is a deployment detail, and publishing it would invite a client to construct paths.
 *
 * The metadata is flattened and pre-formatted. `size_label` is here rather than the raw byte
 * count alone because every screen that shows a file shows a human size, and formatting bytes in
 * four places on the client is four opportunities to disagree about whether a kilobyte is 1000.
 *
 * @mixin PrototypeArtifact
 */
class PrototypeArtifactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $metadata = $this->metadata();

        return [
            'id' => $this->id,
            'prototype_version_id' => $this->prototype_version_id,
            'name' => $this->name,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'size' => $metadata->size,
            'size_label' => $metadata->sizeLabel(),
            'mime_type' => $metadata->mimeType,
            'original_filename' => $metadata->originalFilename,
            'created_by' => $this->created_by,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
