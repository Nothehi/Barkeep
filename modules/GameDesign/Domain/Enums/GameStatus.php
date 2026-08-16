<?php

namespace Modules\GameDesign\Domain\Enums;

/**
 * Where a game project sits in its own life.
 *
 * Status is about the *project*: is anybody working on this, has it been
 * parked, is it finished. It says nothing about how far the design has got —
 * that is {@see DesignPhase}, and the two move independently. A game can be
 * on hold in the middle of playtesting, or active while still just an idea.
 *
 * The transitions below are the whole lifecycle. Encoding them here rather
 * than in a controller is what makes "you cannot un-archive a game" a
 * property of the domain instead of an omission somebody has to remember.
 */
enum GameStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Archived = 'archived';

    /**
     * The status a game starts life in.
     */
    public static function default(): self
    {
        return self::Draft;
    }

    /**
     * The statuses this one may legally move to.
     *
     * Archived is deliberately terminal: an archived game is read-only, and
     * restoring one is a decision the product has not made yet. When it does,
     * this is the single line that changes.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Draft => [self::Active, self::Archived],
            self::Active => [self::OnHold, self::Completed, self::Archived],
            self::OnHold => [self::Active, self::Archived],
            self::Completed => [self::Archived],
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
     * Determine whether the game and everything inside it may still change.
     *
     * Completed games stay editable: finishing a design is not the same as
     * putting it away, and a published game still gets errata.
     */
    public function allowsModification(): bool
    {
        return $this !== self::Archived;
    }

    /**
     * Determine whether the game has any life left in it.
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
            self::OnHold => __('On hold'),
            self::Completed => __('Completed'),
            self::Archived => __('Archived'),
        };
    }

    /**
     * The verb offered for moving from this status to the given one.
     *
     * Lifecycle changes are explicit actions rather than a free choice from a
     * dropdown, so the wording of each one belongs beside the matrix that
     * allows it and the client renders whatever the server offers. The
     * wording depends on both ends — reaching Active from Draft is starting
     * work, reaching it from On hold is picking it back up.
     */
    public function transitionLabelTo(self $target): string
    {
        return match ([$this, $target]) {
            [self::Draft, self::Active] => __('Start designing'),
            [self::OnHold, self::Active] => __('Resume'),
            [self::Active, self::OnHold] => __('Put on hold'),
            [self::Active, self::Completed] => __('Mark complete'),
            default => match ($target) {
                self::Draft => __('Return to draft'),
                self::Active => __('Make active'),
                self::OnHold => __('Put on hold'),
                self::Completed => __('Mark complete'),
                self::Archived => __('Archive'),
            },
        };
    }

    /**
     * The message shown when a change is refused because of this status.
     */
    public function deniedReason(): string
    {
        return match ($this) {
            self::Draft => __('This game is a draft.'),
            self::Active => __('This game is active.'),
            self::OnHold => __('This game is on hold.'),
            self::Completed => __('This game has been completed.'),
            self::Archived => __('This game has been archived and is read-only.'),
        };
    }
}
