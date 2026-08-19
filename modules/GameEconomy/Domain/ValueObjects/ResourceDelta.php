<?php

namespace Modules\GameEconomy\Domain\ValueObjects;

/**
 * How much of one resource an action moves, in and out.
 *
 * The unit an action's profitability is reported in. There is one of these per
 * resource the action touches, and they are never summed across resources —
 * doing that would require a rate of exchange between wood and gold, which is
 * precisely the number this module refuses to invent.
 */
final readonly class ResourceDelta
{
    public function __construct(
        public string $resourceId,
        public string $resourceName,
        public ?string $unit,
        public Quantity $cost,
        public Quantity $reward,
    ) {}

    /**
     * What the action leaves the player holding: reward less cost.
     */
    public function net(): Quantity
    {
        return $this->reward->minus($this->cost);
    }

    /**
     * Determine whether the player ends up with more of it than they started.
     */
    public function isGain(): bool
    {
        return $this->net()->isPositive();
    }

    /**
     * Determine whether the action spends more of it than it returns.
     */
    public function isSpend(): bool
    {
        return $this->net()->isNegative();
    }
}
