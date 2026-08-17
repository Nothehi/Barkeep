<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\ChecklistItemData;
use Modules\DesignFramework\Application\Services\FrameworkModificationGuard;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\Identity\Domain\Models\User;

/**
 * Change a checklist requirement.
 *
 * `required` is only written when the caller sent it, because a checkbox that was left
 * off a partial request must not silently promote an optional item into a required one
 * — and a required item quietly becoming optional would move every following game's
 * progress.
 *
 * An item cannot be moved between checklists. That would be a different operation with
 * a different meaning: a requirement belongs to the gate it is part of, and "prototype
 * readiness" losing an item to "playtest readiness" is two edits, not one move.
 */
final class UpdateChecklistItem
{
    public function __construct(private readonly FrameworkModificationGuard $guard) {}

    public function handle(User $actor, ChecklistItem $item, ChecklistItemData $data): ChecklistItem
    {
        $this->guard->ensureChecklistItemIsModifiable($item);

        if ($data->sent('title') && $data->title !== null) {
            $item->title = $data->title;
        }

        if ($data->sent('description')) {
            $item->description = $data->description;
        }

        if ($data->sent('satisfied_by')) {
            $item->satisfied_by = $data->satisfiedBy;
        }

        if ($data->sent('required')) {
            $item->required = $data->required;
        }

        $item->save();

        return $item;
    }
}
