<?php

namespace Modules\DesignFramework\Application\DTOs;

use Modules\DesignFramework\Domain\ValueObjects\ProgressRatio;

/**
 * How far one game has got through its whole framework.
 *
 * A read model, counted on demand rather than stored. Section 20 says not to
 * persist this initially and the reason is worth keeping in view: a stored
 * percentage is a fourth fact that can disagree with the three it came from, and
 * the disagreement is only ever noticed later.
 *
 * The totals cover the version's *whole* content, including anything filed under
 * no phase — a criterion that applies across the methodology still has to be
 * answered — while {@see $phases} counts how many of the version's stages are
 * finished, and {@see $phaseProgress} carries them one by one for the progress
 * screen.
 *
 * Section 20 also says the percentage is not for gamification yet, and nothing in
 * this module uses it for anything but drawing a bar. Whatever eventually wants to
 * reward progress should read the events, not this.
 */
final readonly class FrameworkProgress
{
    /**
     * @param  array<int, PhaseProgress>  $phaseProgress  one entry per phase, in order
     */
    public function __construct(
        public string $gameFrameworkId,
        public string $frameworkVersionId,
        public ProgressRatio $phases,
        public ProgressRatio $criteria,
        public ProgressRatio $practices,
        public ProgressRatio $checklistItems,
        public ProgressRatio $prompts,
        public ProgressRatio $overall,
        public array $phaseProgress,
    ) {}

    /**
     * Build a framework's progress from its parts.
     *
     * `overall` sums the three counted ratios across the whole version rather than
     * averaging the phase percentages. Averaging would make a phase containing one
     * checklist item weigh as much as one containing thirty, which is how a
     * progress bar starts lying about how much work is left.
     *
     * @param  array<int, PhaseProgress>  $phaseProgress
     */
    public static function of(
        string $gameFrameworkId,
        string $frameworkVersionId,
        ProgressRatio $criteria,
        ProgressRatio $practices,
        ProgressRatio $checklistItems,
        ProgressRatio $prompts,
        array $phaseProgress,
    ): self {
        $countable = array_values(array_filter(
            $phaseProgress,
            fn (PhaseProgress $phase): bool => ! $phase->overall->isEmpty(),
        ));

        $completedPhases = count(array_filter(
            $countable,
            fn (PhaseProgress $phase): bool => $phase->isComplete(),
        ));

        return new self(
            gameFrameworkId: $gameFrameworkId,
            frameworkVersionId: $frameworkVersionId,
            phases: ProgressRatio::of($completedPhases, count($countable)),
            criteria: $criteria,
            practices: $practices,
            checklistItems: $checklistItems,
            prompts: $prompts,
            overall: $criteria->plus($practices)->plus($checklistItems),
            phaseProgress: $phaseProgress,
        );
    }

    /**
     * The single number section 20 asks for.
     */
    public function percentage(): int
    {
        return $this->overall->percentage();
    }

    /**
     * Determine whether the game has worked through everything the framework asks.
     *
     * Note that this is not the same as the adoption being complete. Declaring
     * yourself finished with a methodology is a decision a designer makes; this is
     * arithmetic, and plenty of studios stop at eighty per cent because the last
     * twenty was about a production run they are not doing.
     */
    public function isComplete(): bool
    {
        return $this->overall->isComplete();
    }
}
