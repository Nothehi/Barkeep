<?php

namespace Modules\GameRules\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameRules\Domain\ValueObjects\ValidationError;

/**
 * One thing the validator found.
 *
 * Everything a client needs to render and act on a finding, and nothing it would
 * have to look up: the code so it can be grouped, the severity so it can be
 * coloured, the entity type and id so it can be *linked to*, and three pieces of
 * already-worded text.
 *
 * The three pieces of text do different jobs and are kept apart deliberately.
 * `title` is the heading the finding is filed under, `message` names the thing it
 * is about, and `explanation` says why the check exists at all — which is the one
 * a designer reads when they disagree with it.
 *
 * @mixin ValidationError
 */
class ValidationErrorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code->value,
            'severity' => $this->severity()->value,
            'severity_label' => $this->severity()->label(),
            'entity_type' => $this->entityType->value,
            'entity_type_label' => $this->entityType->label(),
            'entity_id' => $this->entityId,
            'subject' => $this->subject,
            'title' => $this->title(),
            'message' => $this->message,
            'explanation' => $this->explanation(),
        ];
    }
}
