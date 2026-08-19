<?php

namespace Modules\GameEconomy\Domain\Enums;

use Modules\GameEconomy\Domain\Enums\Contracts\Labelled;

/**
 * Where a balance configuration sits in its own life.
 *
 * Three states and one direction, and the direction is the whole point.
 * A profile is drafted while somebody is tuning it, active while it is *the*
 * configuration for its design state, and archived once the studio has moved on.
 *
 * Archived is terminal. Un-archiving would mean a configuration a playtest was
 * run against could quietly become the current one again, and every observation
 * filed against it would start describing numbers that had changed underneath.
 * A studio returning to an old shape copies it into a new draft, which is also
 * how they would describe it out loud.
 *
 * Only one profile per game version may be active at a time — enforced by a
 * partial unique index rather than by this enum, because "one of these" is a
 * fact about the set and not about any member of it.
 */
enum BalanceProfileStatus: string implements Labelled
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    /**
     * The status a profile starts life in.
     */
    public static function default(): self
    {
        return self::Draft;
    }

    /**
     * The statuses this one may legally move to.
     *
     * Draft reaches archived directly, because a configuration somebody started
     * and abandoned is a real outcome and should not have to be put into play
     * first in order to be put away.
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
     * Determine whether the profile's configuration may still be changed.
     *
     * An active profile is still editable, which is the deliberate difference
     * between this and a snapshot. Tuning is what a studio does to the
     * configuration that is in play; freezing it is what snapshots are for.
     */
    public function allowsModification(): bool
    {
        return $this !== self::Archived;
    }

    /**
     * Determine whether the profile has any life left in it.
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
            self::Active => __('Activate profile'),
            self::Archived => __('Archive profile'),
        };
    }

    /**
     * The message shown when a change is refused because of this status.
     */
    public function deniedReason(): string
    {
        return match ($this) {
            self::Draft => __('This balance profile is still a draft.'),
            self::Active => __('This balance profile is in play.'),
            self::Archived => __('This balance profile was archived and is read-only.'),
        };
    }
}
