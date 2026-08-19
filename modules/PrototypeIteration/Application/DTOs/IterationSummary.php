<?php

namespace Modules\PrototypeIteration\Application\DTOs;

use Modules\PrototypeIteration\Domain\Models\Iteration;

/**
 * What a design cycle produced, counted rather than stored.
 *
 * Every figure here is derived on read. Nothing is persisted, no rollup table
 * exists and no listener maintains a counter — section 33 asks for exactly that,
 * and the reason is the one that applies everywhere in this platform: the moment
 * a stored count and the rows it describes can disagree, somebody spends an
 * afternoon finding out which one is lying.
 *
 * It is affordable because the numbers are small. A cycle has a handful of
 * changes and a couple of decisions; the whole summary is a few aggregate
 * queries plus one pass through the attached playtests. When somebody eventually
 * wants figures across a studio's whole history, that is a reporting problem for
 * the analytics capability rather than a reason to denormalise this.
 *
 * ## The pairs
 *
 * Three of these are counted twice on purpose, and each pair answers a question
 * the single number cannot:
 *
 * - experiments against completed experiments, because an iteration that closed
 *   with two of its three experiments still running tested less than its
 *   headline count suggests — and this module refuses to auto-complete them, so
 *   the gap is real and visible;
 * - decisions against accepted decisions, because a cycle that proposed four
 *   conclusions and agreed none is a cycle that did not conclude;
 * - playtests against the observations and feedback they produced, because
 *   "four playtests" and "four playtests that between them produced two
 *   observations" describe very different evidence.
 *
 * The last three figures come from Playtesting through this module's own adapter
 * rather than from a copy held here. That is what keeps the numbers on an
 * iteration screen equal to the numbers on the playtest's own screen.
 *
 * Two absences are deliberate. Nothing here scores the iteration, and nothing
 * interprets the hypothesis: "three partials in a row" means something to the
 * person who ran them and nothing Barkeep is in a position to judge.
 */
final readonly class IterationSummary
{
    public function __construct(
        public Iteration $iteration,
        public int $changeCount,
        public int $experimentCount,
        public int $completedExperimentCount,
        public int $decisionCount,
        public int $acceptedDecisionCount,
        public int $evidenceCount,
        public int $playtestCount,
        public int $sessionCount,
        public int $observationCount,
        public int $feedbackCount,
    ) {}

    /**
     * The summary of a cycle nobody has done anything in yet.
     *
     * Zeroes throughout, which is the honest shape here — unlike an average, a
     * count of nothing really is nought.
     */
    public static function empty(Iteration $iteration): self
    {
        return new self(
            iteration: $iteration,
            changeCount: 0,
            experimentCount: 0,
            completedExperimentCount: 0,
            decisionCount: 0,
            acceptedDecisionCount: 0,
            evidenceCount: 0,
            playtestCount: 0,
            sessionCount: 0,
            observationCount: 0,
            feedbackCount: 0,
        );
    }

    /**
     * Determine whether the cycle recorded any design work at all.
     *
     * The question behind whether a summary is worth drawing, and behind the
     * warning shown next to "complete iteration" on a cycle that has nothing in
     * it. It is a warning rather than a refusal: an iteration that concluded
     * "the change was unnecessary" recorded no changes and is a perfectly real
     * outcome.
     */
    public function hasWork(): bool
    {
        return $this->changeCount > 0
            || $this->experimentCount > 0
            || $this->decisionCount > 0
            || $this->playtestCount > 0;
    }

    /**
     * Determine whether the cycle gathered anything to reason from.
     */
    public function hasEvidence(): bool
    {
        return $this->observationCount > 0 || $this->feedbackCount > 0;
    }

    /**
     * Determine whether every experiment the cycle started was seen through.
     *
     * True for a cycle with no experiments, which is the right answer: nothing
     * was left hanging. The interface uses this to decide whether to point out
     * unfinished experiments before an iteration is closed.
     */
    public function experimentsAreSettled(): bool
    {
        return $this->completedExperimentCount === $this->experimentCount;
    }
}
