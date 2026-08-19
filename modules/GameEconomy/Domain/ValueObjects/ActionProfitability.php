<?php

namespace Modules\GameEconomy\Domain\ValueObjects;

/**
 * What an action actually does to a player's holdings.
 *
 * Reported as one line per resource rather than as a single number, and that is
 * the whole design of it. "Build costs 5 wood and 2 stone and pays nothing" is
 * an answer; "Build is worth -7" is a fiction that required deciding wood and
 * stone are interchangeable.
 *
 * Section 26 of the brief says this outright, and the shape of this class is how
 * it is enforced: there is no field here that could hold a total, so nothing
 * downstream can render one.
 *
 * Effects are counted rather than valued. An action that unlocks a technology is
 * not free even though it pays out nothing, and the count is what stops the
 * analysis from calling it pointless.
 */
final readonly class ActionProfitability
{
    /**
     * @param  list<ResourceDelta>  $deltas
     */
    public function __construct(
        public string $actionId,
        public string $actionName,
        public array $deltas,
        public int $effectCount,
    ) {}

    /**
     * The resources the action spends.
     *
     * @return list<ResourceDelta>
     */
    public function spends(): array
    {
        return array_values(array_filter($this->deltas, fn (ResourceDelta $delta): bool => $delta->cost->isPositive()));
    }

    /**
     * The resources the action pays out.
     *
     * @return list<ResourceDelta>
     */
    public function pays(): array
    {
        return array_values(array_filter($this->deltas, fn (ResourceDelta $delta): bool => $delta->reward->isPositive()));
    }

    /**
     * Determine whether the action takes anything to perform.
     */
    public function hasCost(): bool
    {
        return $this->spends() !== [];
    }

    /**
     * Determine whether the action gives anything back.
     */
    public function hasReward(): bool
    {
        return $this->pays() !== [];
    }

    /**
     * Determine whether the action does anything at all.
     *
     * An effect counts. An action that unlocks a building without paying out is
     * a real action, and treating it as empty would put a warning on half the
     * technology tree of any game that has one.
     */
    public function hasOutcome(): bool
    {
        return $this->hasReward() || $this->effectCount > 0;
    }

    /**
     * The resources this action returns more of than it takes.
     *
     * The shape behind "conversion creates more resources than it consumes":
     * an action that costs 2 wood and pays 3 wood is a money printer, and it is
     * only visible per resource — which is why it is asked here rather than of a
     * total.
     *
     * @return list<ResourceDelta>
     */
    public function multipliedResources(): array
    {
        return array_values(array_filter(
            $this->deltas,
            fn (ResourceDelta $delta): bool => $delta->cost->isPositive() && $delta->isGain(),
        ));
    }
}
