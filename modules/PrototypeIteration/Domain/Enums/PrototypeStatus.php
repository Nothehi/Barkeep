<?php

namespace Modules\PrototypeIteration\Domain\Enums;

/**
 * Where a prototype sits in its own life.
 *
 * Three states and one direction. A prototype is drafted while it is being
 * assembled, active while it is the thing being built and tested, and archived
 * once the design has moved past it.
 *
 * The transitions below are the whole lifecycle, and archived is terminal on
 * purpose. A prototype accumulates versions, and those versions accumulate
 * iterations — so un-archiving one would mean the record of "we stopped
 * working on this in March" is a field somebody can quietly flip. When a
 * studio picks the approach back up, that is a new prototype, which is also
 * how a designer talks about it.
 *
 * Note what this status does *not* control: whether the prototype's history
 * stays readable. An archived prototype refuses new versions and keeps every
 * iteration ever run against it legible, because the iterations are the record
 * this module exists to preserve.
 */
enum PrototypeStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    /**
     * The status a prototype starts life in.
     */
    public static function default(): self
    {
        return self::Draft;
    }

    /**
     * The statuses this one may legally move to.
     *
     * Draft reaches archived directly, because a prototype somebody started
     * assembling and then abandoned is a real outcome and should not have to
     * be activated first to be put away.
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
     * Determine whether the prototype's own details may still be changed.
     */
    public function allowsModification(): bool
    {
        return $this !== self::Archived;
    }

    /**
     * Determine whether the prototype may still gain versions.
     */
    public function allowsVersions(): bool
    {
        return $this->allowsModification();
    }

    /**
     * Determine whether the prototype has any life left in it.
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
     *
     * Lifecycle changes are explicit actions rather than a free choice from a
     * dropdown, so the wording of each one belongs beside the matrix that
     * allows it and the client renders whatever the server offers.
     */
    public function transitionLabelTo(self $target): string
    {
        return match ($target) {
            self::Draft => __('Return to draft'),
            self::Active => __('Activate prototype'),
            self::Archived => __('Archive prototype'),
        };
    }

    /**
     * The message shown when a change is refused because of this status.
     */
    public function deniedReason(): string
    {
        return match ($this) {
            self::Draft => __('This prototype is still being assembled.'),
            self::Active => __('This prototype is in use.'),
            self::Archived => __('This prototype was archived and is read-only.'),
        };
    }
}
