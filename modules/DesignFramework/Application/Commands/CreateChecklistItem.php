<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\ChecklistItemData;
use Modules\DesignFramework\Application\Services\ContentSequencer;
use Modules\DesignFramework\Application\Services\ContentSlugAllocator;
use Modules\DesignFramework\Application\Services\FrameworkModificationGuard;
use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\FrameworkRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Add a requirement to a checklist.
 *
 * The address is unique within the checklist rather than within the version, which is
 * the one place this module's uniqueness scope differs — two phases can both have a
 * "win condition implemented" item, and refusing the second would be a rule about slugs
 * imposed on somebody writing a list.
 *
 * `required` defaults to true. An optional item is shown and can be ticked but does not
 * count towards a game's progress, which is what lets an author add a nice-to-have
 * without every studio's numbers moving.
 *
 * No event. A checklist is authored as a whole, and an event per item would be noise
 * nobody consumes — {@see ChecklistCreated} is the announcement.
 */
final class CreateChecklistItem
{
    public function __construct(
        private readonly FrameworkModificationGuard $guard,
        private readonly FrameworkRepository $frameworks,
        private readonly ContentSequencer $sequencer,
        private readonly ContentSlugAllocator $slugs,
    ) {}

    public function handle(User $creator, Checklist $checklist, ChecklistItemData $data): ChecklistItem
    {
        $this->guard->ensureChecklistIsModifiable($checklist);

        $siblings = $this->frameworks->checklistItemSiblings($checklist);
        $title = (string) $data->title;

        $item = new ChecklistItem;

        $item->fill([
            'title' => $title,
            'description' => $data->description,
            'required' => $data->required,
        ]);

        $item->checklist_id = $checklist->getKey();
        $item->slug = $this->slugs->derive($siblings, $title)->value;
        $item->position = $this->sequencer->append($siblings);

        $item->save();

        $item->setRelation('checklist', $checklist);

        return $item;
    }
}
