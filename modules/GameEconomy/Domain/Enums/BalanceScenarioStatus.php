<?php

namespace Modules\GameEconomy\Domain\Enums;

use Modules\GameEconomy\Domain\Enums\Contracts\Labelled;

/**
 * Where a hypothetical sits in its own life.
 *
 * The same three words as a profile's lifecycle and deliberately not the same
 * enum, because the constraint around them differs: a design state has exactly
 * one active profile, and any number of active scenarios. A studio comparing
 * two-player against four-player needs both live at once, and sharing the
 * profile's enum would invite somebody to share its uniqueness rule too.
 */
enum BalanceScenarioStatus: string implements Labelled
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    /**
     * The status a scenario starts life in.
     */
    public static function default(): self
    {
        return self::Draft;
    }

    /**
     * The statuses this one may legally move to.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Draft => [self::Active, self::Archived],
            self::Active => [self::Archived],
            self::Archived => [],
        };
    }

    /**
     * Determine whether this status may move to the given one.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->transitions(), strict: true);
    }

    /**
     * Determine whether the scenario and its overrides may still be changed.
     */
    public function allowsModification(): bool
    {
        return $this !== self::Archived;
    }

    /**
     * Determine whether the scenario has any life left in it.
     */
    public function isTerminal(): bool
    {
        return $this->transitions() === [];
    }

    /**
     * A human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Active => __('Active'),
            self::Archived => __('Archived'),
        };
    }

    /**
     * The verb offered for moving from this status to the given one.
     */
    public function transitionLabelTo(self $target): string
    {
        return match ($target) {
            self::Draft => __('Return to draft'),
            self::Active => __('Activate scenario'),
            self::Archived => __('Archive scenario'),
        };
    }

    /**
     * The message shown when a change is refused because of this status.
     */
    public function deniedReason(): string
    {
        return match ($this) {
            self::Draft => __('This scenario is still a draft.'),
            self::Active => __('This scenario is in use.'),
            self::Archived => __('This scenario was archived and is read-only.'),
        };
    }
}
