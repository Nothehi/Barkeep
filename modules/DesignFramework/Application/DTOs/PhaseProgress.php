<?php

namespace Modules\DesignFramework\Application\DTOs;

use Modules\DesignFramework\Domain\ValueObjects\ProgressRatio;

/**
 * How far one game has got through one phase of its framework.
 *
 * A read model. Nothing persists this: it is counted on read from the version's
 * content and the game's own records, which is what stops a stored percentage from
 * disagreeing with the rows it came from.
 *
 * The three counted ratios are the ones section 41 names — evaluated criteria,
 * completed practices, ticked required checklist items — and `overall` is their sum
 * rather than an average of their percentages. Summing weights each by how much
 * work it represents, so a phase with one criterion and twenty checklist items does
 * not treat the criterion as half the phase.
 *
 * Prompts are counted and reported but deliberately excluded from `overall`. A
 * prompt has no right answer, and letting it advance a progress bar would reward
 * typing something over thinking about it. The count is here because a phase page
 * genuinely wants to say "3 of 5 answered".
 */
final readonly class PhaseProgress
{
    public function __construct(
        public string $phaseId,
        public string $slug,
        public string $name,
        public int $position,
        public ProgressRatio $criteria,
        public ProgressRatio $practices,
        public ProgressRatio $checklistItems,
        public ProgressRatio $prompts,
        public ProgressRatio $overall,
    ) {}

    /**
     * Build a phase's progress from its parts, summing what counts.
     */
    public static function of(
        string $phaseId,
        string $slug,
        string $name,
        int $position,
        ProgressRatio $criteria,
        ProgressRatio $practices,
        ProgressRatio $checklistItems,
        ProgressRatio $prompts,
    ): self {
        return new self(
            phaseId: $phaseId,
            slug: $slug,
            name: $name,
            position: $position,
            criteria: $criteria,
            practices: $practices,
            checklistItems: $checklistItems,
            prompts: $prompts,
            overall: $criteria->plus($practices)->plus($checklistItems),
        );
    }

    /**
     * How far along, from 0 to 100.
     */
    public function percentage(): int
    {
        return $this->overall->percentage();
    }

    /**
     * Determine whether everything countable in the phase has been done.
     *
     * A phase with nothing to count is not complete. There was nothing to do, which
     * is a different statement from having done it — and a framework author must not
     * be able to raise everybody's progress by writing a phase with no content in it.
     */
    public function isComplete(): bool
    {
        return $this->overall->isComplete();
    }

    /**
     * Determine whether the phase asks anything of a designer at all.
     */
    public function isEmpty(): bool
    {
        return $this->overall->isEmpty() && $this->prompts->isEmpty();
    }
}
