<?php

namespace Modules\DesignFramework\Domain\ValueObjects;

use Stringable;

/**
 * How much of something has been done, as a completed-out-of-total pair.
 *
 * Every count in a framework progress report is one of these, and they are
 * carried as a pair rather than as a percentage on purpose. "3 of 4" and "75%"
 * are not the same claim: the first says how much work is left, the second
 * invites comparison between games whose frameworks have different amounts of
 * content in them.
 *
 * The percentage is derived when a screen genuinely needs a bar to fill, and it
 * is derived here so that the rounding has one definition. Two rules matter:
 *
 * - an empty total is 0%, not a division by zero and not 100%. A phase with no
 *   criteria has not been assessed; claiming it is finished would let a
 *   framework author raise everybody's progress by deleting content.
 * - a partial result never rounds up to 100. Being told a phase is complete
 *   when one checklist item is outstanding is the single most annoying way a
 *   progress bar can lie.
 */
final readonly class ProgressRatio implements Stringable
{
    private function __construct(
        public int $completed,
        public int $total,
    ) {}

    /**
     * Build a ratio, clamping nonsense rather than raising.
     *
     * Counts come from `count()` and from aggregate queries, so a negative
     * total is not a domain event worth an exception — but a completed count
     * above the total would silently produce percentages over 100, so it is
     * capped.
     */
    public static function of(int $completed, int $total): self
    {
        $total = max(0, $total);

        return new self(min(max(0, $completed), $total), $total);
    }

    /**
     * The empty ratio: nothing to do, nothing done.
     */
    public static function none(): self
    {
        return new self(0, 0);
    }

    /**
     * Add another ratio to this one.
     *
     * Used to roll a phase's checklists, practices and criteria into one
     * figure. Summing the pairs rather than averaging the percentages is what
     * makes a phase with one criterion and twenty checklist items weight them
     * by how much work each represents.
     */
    public function plus(self $other): self
    {
        return new self($this->completed + $other->completed, $this->total + $other->total);
    }

    /**
     * How far along, from 0 to 100.
     */
    public function percentage(): int
    {
        if ($this->total === 0) {
            return 0;
        }

        if ($this->isComplete()) {
            return 100;
        }

        return min(99, (int) floor($this->completed / $this->total * 100));
    }

    /**
     * Determine whether everything counted has been done.
     *
     * An empty ratio is not complete. There was nothing to do, which is a
     * different statement from having done it, and progress screens say so.
     */
    public function isComplete(): bool
    {
        return $this->total > 0 && $this->completed >= $this->total;
    }

    /**
     * Determine whether there is anything to count at all.
     */
    public function isEmpty(): bool
    {
        return $this->total === 0;
    }

    /**
     * How many are outstanding.
     */
    public function remaining(): int
    {
        return $this->total - $this->completed;
    }

    /**
     * How the ratio is written wherever people read it: "3 / 4".
     */
    public function __toString(): string
    {
        return $this->completed.' / '.$this->total;
    }
}
