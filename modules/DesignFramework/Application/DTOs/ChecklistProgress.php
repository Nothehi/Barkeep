<?php

namespace Modules\DesignFramework\Application\DTOs;

use Modules\DesignFramework\Domain\Models\Checklist;
use Modules\DesignFramework\Domain\ValueObjects\ProgressRatio;

/**
 * One checklist, and how much of it a game has ticked.
 *
 * The read model behind "Prototype readiness — 2 / 4 completed". The list itself is
 * framework content and the ticks belong to the game's adoption, so this pairs the
 * two without either learning about the other.
 *
 * `required` counts only the items that have to be met, which is what the summary
 * line reports; `all` counts every item including the optional ones, which is what
 * the checkboxes render. Keeping both is why an author can add a nice-to-have
 * without a studio's summary going backwards.
 *
 * @property-read Checklist $checklist
 */
final readonly class ChecklistProgress
{
    /**
     * @param  array<string, bool>  $completions  item id => whether it is ticked
     */
    public function __construct(
        public Checklist $checklist,
        public ProgressRatio $required,
        public ProgressRatio $all,
        public array $completions,
    ) {}

    /**
     * Determine whether a particular item has been ticked.
     */
    public function isItemComplete(string $itemId): bool
    {
        return $this->completions[$itemId] ?? false;
    }

    /**
     * Determine whether every required item has been met.
     */
    public function isSatisfied(): bool
    {
        return $this->required->isComplete();
    }
}
