<?php

namespace Modules\DesignFramework\Application\Queries;

use Modules\DesignFramework\Application\DTOs\FrameworkProgress;
use Modules\DesignFramework\Application\Services\FrameworkProgressCalculator;
use Modules\DesignFramework\Domain\Models\GameFramework;

/**
 * How far a game has got through its methodology.
 *
 * A thin front door onto `FrameworkProgressCalculator`, which is where all the arithmetic
 * lives. The indirection is the point of section 41: controllers and screens ask this
 * question and are handed an answer, so when the weighting evolves — and it will, because
 * "should answering prompts count?" is a real product argument — exactly one file changes.
 *
 * Nothing is cached and nothing is stored. Every figure is counted from the version's
 * content and the game's own records, because a stored percentage is a fourth fact that can
 * disagree with the three it came from.
 */
final class GetGameFrameworkProgress
{
    public function __construct(private readonly FrameworkProgressCalculator $calculator) {}

    public function handle(GameFramework $adoption): FrameworkProgress
    {
        return $this->calculator->for($adoption);
    }
}
