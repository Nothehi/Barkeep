<?php

namespace Modules\GameEconomy\Domain\ValueObjects;

/**
 * How much of one resource enters and leaves the economy.
 *
 * The three figures are reported separately rather than as a single net number,
 * because a resource with 12 in and 8 out and a resource with 2 in and 0 out
 * both net +4 and are completely different games. The net is the headline and
 * the two halves are the reason for it, so both travel together everywhere.
 *
 * Costs and rewards on actions are counted alongside the flows, since an action
 * that spends five wood removes wood from the economy whether or not anybody
 * also wrote a consumption flow for it. Which side each contribution lands on
 * comes from the flow type's own `direction()`, so a flow type added later is
 * counted correctly here without this class changing.
 */
final readonly class ResourceNetFlow
{
    public function __construct(
        public string $resourceId,
        public string $resourceName,
        public Quantity $generation,
        public Quantity $consumption,
    ) {}

    /**
     * A resource nothing moves at all.
     */
    public static function still(string $resourceId, string $resourceName): self
    {
        return new self($resourceId, $resourceName, Quantity::zero(), Quantity::zero());
    }

    /**
     * What the resource does on balance: generation less consumption.
     */
    public function net(): Quantity
    {
        return $this->generation->minus($this->consumption);
    }

    /**
     * Determine whether anything at all produces this resource.
     */
    public function hasGeneration(): bool
    {
        return $this->generation->isPositive();
    }

    /**
     * Determine whether anything at all spends it.
     */
    public function hasConsumption(): bool
    {
        return $this->consumption->isPositive();
    }

    /**
     * Determine whether the resource piles up over time.
     */
    public function isSurplus(): bool
    {
        return $this->net()->isPositive();
    }

    /**
     * Determine whether players run out of it.
     */
    public function isDeficit(): bool
    {
        return $this->net()->isNegative();
    }

    /**
     * Determine whether what arrives exactly matches what leaves.
     */
    public function isBalanced(): bool
    {
        return $this->net()->isZero();
    }
}
