<?php

namespace Modules\PrototypeIteration\Application\DTOs;

/**
 * How much building and iterating a studio has done, across every game.
 *
 * The module's contribution to the app's home screen. Three numbers rather
 * than a distribution, because that is what this module can say honestly at a
 * studio-wide altitude: how many prototypes exist, how many cycles have been
 * run against them, and how many of those cycles are still open. Anything
 * finer — which changes, whose decisions, what the evidence said — belongs to
 * the iteration's own screen, where there is room to read it.
 *
 * Every figure is derived on read, for the reason given in
 * {@see IterationSummary}: a stored count that can disagree with the rows it
 * describes costs somebody an afternoon.
 */
final readonly class WorkspaceIterationActivity
{
    public function __construct(
        public int $prototypeCount,
        public int $iterationCount,
        public int $openIterationCount,
    ) {}

    /**
     * Determine whether anything has been built here yet.
     *
     * The question behind whether the dashboard draws these figures or leaves
     * the card out — a studio still writing down ideas does not need to be
     * told it has no prototypes.
     */
    public function hasWork(): bool
    {
        return $this->prototypeCount > 0 || $this->iterationCount > 0;
    }
}
