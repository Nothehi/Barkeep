<?php

namespace Modules\PrototypeIteration\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\PrototypeIteration\Application\DTOs\IterationTimeline;
use Modules\PrototypeIteration\Application\DTOs\TimelineEntry;

/**
 * The representation of a design cycle as it happened.
 *
 * A flat list of already-worded entries, in order, which is exactly what the timeline
 * interaction needs and deliberately not a nested structure the client has to walk. The
 * entries came from five kinds of record across four tables and one other bounded context;
 * flattening them in the application layer is what lets this resource be trivial and lets the
 * client render one loop.
 *
 * Every label here — the kind, the badge — was produced by an enum in the domain. A client that
 * mapped statuses to words itself would be a second copy of the vocabulary, going stale the
 * first time a category was renamed.
 *
 * `counts` is the one field that is not pre-worded, because this application pluralises on the
 * client against the shared catalogue. A playtest entry hands over its numbers and lets the
 * interface say them in the reader's own language.
 *
 * @mixin IterationTimeline
 */
class IterationTimelineResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'iteration_id' => $this->iteration->id,
            'is_empty' => $this->isEmpty(),
            'tally' => $this->tally(),
            'entries' => $this->entries
                ->map(fn (TimelineEntry $entry): array => [
                    'kind' => $entry->kind->value,
                    'kind_label' => $entry->kind->label(),
                    'is_lifecycle' => $entry->kind->isLifecycle(),
                    'id' => $entry->id,
                    'at' => $entry->at?->format(DATE_ATOM),
                    'title' => $entry->title,
                    'body' => $entry->body,
                    'badge' => $entry->badge,
                    'status' => $entry->status,
                    'reference' => $entry->reference,
                    'counts' => $entry->counts,
                ])
                ->all(),
        ];
    }
}
