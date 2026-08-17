<?php

namespace Modules\DesignFramework\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\DesignFramework\Application\DTOs\ChecklistProgress;
use Modules\DesignFramework\Domain\Enums\ChecklistItemState;
use Modules\DesignFramework\Domain\Models\ChecklistItem;

/**
 * One checklist, paired with which of its requirements a game has met.
 *
 * The one place framework content and a studio's own state travel together, and they are kept
 * visibly distinct: the list is rendered through its own resource, and the ticks arrive as a
 * separate `items` array keyed by item id.
 *
 * Doing the pairing server-side rather than sending both collections and letting the client
 * join them is what keeps the required-versus-optional distinction correct — the summary line
 * counts only required items, the checkboxes render all of them, and a client working that out
 * for itself would eventually get one of the two wrong.
 *
 * @mixin ChecklistProgress
 */
class ChecklistProgressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'checklist' => ChecklistResource::make($this->checklist),
            'required' => PhaseProgressResource::ratio($this->required),
            'all' => PhaseProgressResource::ratio($this->all),
            'is_satisfied' => $this->isSatisfied(),
            'items' => $this->checklist->items
                ->map(fn (ChecklistItem $item): array => [
                    'checklist_item_id' => (string) $item->getKey(),
                    'state' => ChecklistItemState::fromCompletion($this->isItemComplete((string) $item->getKey()))->value,
                    'is_complete' => $this->isItemComplete((string) $item->getKey()),
                ])
                ->values()
                ->all(),
        ];
    }
}
