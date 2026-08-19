<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameEconomy\Domain\ValueObjects\BalanceWarning;

/**
 * The representation of one thing the analysis noticed.
 *
 * The code goes out as well as the wording, and that is the field worth
 * explaining. Wording is translated and will change; the code will not, so it is
 * what an interface groups by, what a test asserts on, and what a studio would
 * eventually filter or mute by. Sending only prose would make every one of those
 * depend on a string somebody might improve.
 *
 * `entity_type` and `entity_id` are what let a finding link to the thing it is
 * about. The type is an enum value rather than a class name, because a namespace
 * is neither useful nor safe to publish to a browser.
 *
 * @mixin BalanceWarning
 */
class BalanceWarningResource extends JsonResource
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
            'is_error' => $this->isError(),
            'title' => $this->title(),
            'description' => $this->description,
            'explanation' => $this->explanation(),
            'subject' => $this->subject,
            'entity_type' => $this->entityType->value,
            'entity_type_label' => $this->entityType->label(),
            'entity_id' => $this->entityId,
        ];
    }
}
