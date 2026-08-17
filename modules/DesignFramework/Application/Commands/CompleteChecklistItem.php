<?php

namespace Modules\DesignFramework\Application\Commands;

use Modules\DesignFramework\Application\DTOs\CompletionData;
use Modules\DesignFramework\Application\Services\FrameworkContentLocator;
use Modules\DesignFramework\Application\Services\GameFrameworkGuard;
use Modules\DesignFramework\Domain\Events\ChecklistItemCompleted;
use Modules\DesignFramework\Domain\Models\ChecklistItem;
use Modules\DesignFramework\Domain\Models\ChecklistItemCompletion;
use Modules\DesignFramework\Domain\Models\GameFramework;
use Modules\DesignFramework\Infrastructure\Persistence\Repositories\GameFrameworkRepository;
use Modules\Identity\Domain\Models\User;

/**
 * Tick — or untick — one checklist requirement for a game.
 *
 * The framework defines the checklist; the game records its state. Same separation as
 * practices and criteria, for the same reason: one published checklist is read by every
 * game following the version it belongs to.
 *
 * Unticking deletes the row rather than storing a false flag, which is what makes a
 * checklist item genuinely binary rather than a workflow with states. Section 15 asks for
 * exactly that, and the table has no `completed` column to relax it with.
 *
 * The event carries whether this tick *finished* the list, because that is the fact worth
 * hearing about. "Prototype readiness is now complete" is a milestone; "one more box on
 * prototype readiness" is not, and making every consumer count the siblings to tell the
 * difference would mean every consumer reading this module's tables.
 */
final class CompleteChecklistItem
{
    public function __construct(
        private readonly GameFrameworkGuard $guard,
        private readonly FrameworkContentLocator $content,
        private readonly GameFrameworkRepository $adoptions,
    ) {}

    public function handle(
        User $actor,
        GameFramework $adoption,
        ChecklistItem $item,
        CompletionData $data,
    ): ?ChecklistItemCompletion {
        $this->guard->ensureAdoptionAcceptsProgress($adoption);
        $this->content->ensureItemAdopted($adoption, $item);

        $existing = $this->adoptions->findItemCompletion($adoption, $item);

        if (! $data->completed) {
            $existing?->delete();

            return null;
        }

        $completedAt = now()->toImmutable();
        $wasComplete = $existing !== null;

        $completion = $existing ?? new ChecklistItemCompletion;

        $completion->fill(['notes' => $data->notes]);

        $completion->game_framework_id = $adoption->getKey();
        $completion->checklist_item_id = $item->getKey();
        $completion->completed_by = $actor->id;
        $completion->completed_at = $completedAt;

        $completion->save();

        $completion->setRelation('gameFramework', $adoption);
        $completion->setRelation('item', $item);
        $completion->setRelation('completer', $actor);

        if (! $wasComplete) {
            event(new ChecklistItemCompleted(
                completionId: $completion->id,
                gameFrameworkId: $adoption->getKey(),
                gameId: $adoption->game_id,
                checklistId: $item->checklist_id,
                checklistItemId: $item->getKey(),
                completesChecklist: $this->completesChecklist($adoption, $item),
                completedBy: $actor->id,
                completedAt: $completedAt->toDateTimeImmutable(),
            ));
        }

        return $completion;
    }

    /**
     * Determine whether every required item on the list is now ticked.
     *
     * Counted after the save, so the item just written is included. Only the required
     * items count, matching the progress calculation — a list whose optional extras are
     * outstanding is still satisfied.
     */
    private function completesChecklist(GameFramework $adoption, ChecklistItem $item): bool
    {
        $checklist = $item->checklist;

        if ($checklist === null) {
            return false;
        }

        $required = $checklist->requiredItems()->pluck('id')->all();

        if ($required === []) {
            return false;
        }

        $completed = $this->adoptions->completedItemIds($adoption);

        foreach ($required as $requiredId) {
            if (! in_array((string) $requiredId, $completed, strict: true)) {
                return false;
            }
        }

        return true;
    }
}
