<?php

namespace Modules\PrototypeIteration\Domain\Enums;

/**
 * Where a design decision stands.
 *
 * A decision is the point of the whole module: the sentence a designer will
 * read in a year to find out why the game is the way it is. So the states are
 * about *commitment* rather than about progress — a decision is proposed,
 * agreed, refused, or put off until there is more to go on.
 *
 * The transitions are the strictest in the module, and the refusal that
 * matters is accepted → rejected. Reversing an accepted decision in place
 * would rewrite the reason the design changed, leaving the game carrying a
 * change whose stated justification now argues against it. When a studio
 * changes its mind — and they do, constantly — the honest record is a *new*
 * decision in a later iteration saying so, which is also how anybody reading
 * the history would want to find out.
 *
 * Deferred is the one non-terminal ending, because "we will look at this again
 * after the convention" is a real answer and the thing it defers to is usually
 * the next iteration.
 */
enum DecisionStatus: string
{
    case Proposed = 'proposed';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Deferred = 'deferred';

    /**
     * The status a decision starts life in.
     */
    public static function default(): self
    {
        return self::Proposed;
    }

    /**
     * The statuses this one may legally move to.
     *
     * A deferred decision can be taken up again, which is the whole point of
     * deferring one. Accepted and rejected are both terminal — see the note
     * above the enum for why reversal is a new decision rather than an edit.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Proposed => [self::Accepted, self::Rejected, self::Deferred],
            self::Deferred => [self::Accepted, self::Rejected],
            self::Accepted => [],
            self::Rejected => [],
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
     * Determine whether the decision's wording may still be changed.
     *
     * Open while it is still being argued about, frozen the moment it is
     * settled. A studio that edits the text of an accepted decision has
     * changed what the design history says was agreed.
     */
    public function allowsModification(): bool
    {
        return $this === self::Proposed || $this === self::Deferred;
    }

    /**
     * Determine whether the decision has been settled either way.
     */
    public function isSettled(): bool
    {
        return $this === self::Accepted || $this === self::Rejected;
    }

    /**
     * Determine whether the decision has any moves left.
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
            self::Proposed => __('Proposed'),
            self::Accepted => __('Accepted'),
            self::Rejected => __('Rejected'),
            self::Deferred => __('Deferred'),
        };
    }

    /**
     * The verb offered for moving from this status to the given one.
     */
    public function transitionLabelTo(self $target): string
    {
        return match ($target) {
            self::Proposed => __('Reopen decision'),
            self::Accepted => __('Accept'),
            self::Rejected => __('Reject'),
            self::Deferred => __('Defer'),
        };
    }

    /**
     * The message shown when a change is refused because of this status.
     */
    public function deniedReason(): string
    {
        return match ($this) {
            self::Proposed => __('This decision is still open.'),
            self::Accepted => __('This decision was accepted. Record a new decision rather than reversing it.'),
            self::Rejected => __('This decision was rejected. Record a new decision rather than reversing it.'),
            self::Deferred => __('This decision was deferred.'),
        };
    }
}
