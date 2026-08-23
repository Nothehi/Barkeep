<?php

namespace Modules\GameRules\Domain\Enums;

use Modules\GameRules\Domain\Enums\Contracts\Labelled;

/**
 * Where a rule set sits in its own life.
 *
 * Three states and one direction. A rule set is drafted while somebody is
 * writing it, active while it is *the* rule system for its design state, and
 * archived once the studio has moved on.
 *
 * Archived is terminal, and that is the historical-integrity rule in one place:
 * un-archiving would let a rule set a playtest was run against quietly become
 * the current one again, and every observation filed against it would start
 * describing rules that had changed underneath. A studio returning to an older
 * rule system clones it into a new draft, which is also how they would describe
 * it out loud.
 *
 * The lifecycle differs from a balance profile's in one important way, and the
 * difference is deliberate. An active profile is still tunable — tuning is what
 * a studio does to the numbers in play. An active *rule set* is not editable:
 * the rules are what a session was played under, and section 55 of the module
 * brief makes changing them require a clone rather than an edit.
 */
enum RuleSetStatus: string implements Labelled
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    /**
     * The status a rule set starts life in.
     */
    public static function default(): self
    {
        return self::Draft;
    }

    /**
     * The statuses this one may legally move to.
     *
     * Draft reaches archived directly, because a rule set somebody started and
     * abandoned is a real outcome and should not have to be put into play first
     * in order to be put away.
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
     * Determine whether the rules inside the set may still be edited.
     *
     * Only a draft answers yes. This is the single definition of section 55:
     * drafts are editable, active rule sets change only by being cloned, and
     * archived ones are immutable.
     */
    public function allowsModification(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Determine whether the set's own name and description may still change.
     *
     * Looser than {@see allowsModification()} on purpose. Correcting the title
     * of the rule system a session was played under does not change what was
     * played; rewriting one of its rules does.
     */
    public function allowsRenaming(): bool
    {
        return $this !== self::Archived;
    }

    /**
     * Determine whether the rule set has any life left in it.
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
            self::Active => __('Activate rule set'),
            self::Archived => __('Archive rule set'),
        };
    }

    /**
     * The message shown when a change is refused because of this status.
     */
    public function deniedReason(): string
    {
        return match ($this) {
            self::Draft => __('This rule set is still a draft.'),
            self::Active => __('This rule set is in play. Clone it to make changes.'),
            self::Archived => __('This rule set was archived and is read-only.'),
        };
    }
}
