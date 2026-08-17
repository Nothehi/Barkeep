<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\Services\ContentSequencer;
use Modules\DesignFramework\Application\Services\FrameworkModificationGuard;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Move a requirement within its checklist.
 *
 * The most obviously useful reorder in the module: a readiness checklist is read as a
 * sequence of things to get right, and "core action identified" belongs above "failure
 * condition identified" because you cannot do the second without the first.
 */
final class ReorderChecklistItem
{
    public function __construct(
        private readonly FrameworkModificationGuard $guard,
        private readonly FrameworkRepository $frameworks,
        private readonly ContentSequencer $sequencer,
    ) {}

    public function handle(User $actor, ChecklistItem $item, int $position): ChecklistItem
    {
        $this->guard->ensureChecklistItemIsModifiable($item);

        $checklist = $item->checklist;

        if ($checklist !== null) {
            $this->sequencer->move(
                $item,
                $this->frameworks->checklistItemSiblings($checklist),
                $position,
            );
        }

        return $item;
    }
}
