<?php

namespace Modules\DesignFramework\Domain\Enums;

/**
 * Whether a game has met one checklist requirement.
 *
 * Binary, and it stays binary. There is no "in progress", no "not applicable",
 * no "blocked" — a checklist whose items have states is a workflow engine, and
 * a designer ticking off "win condition implemented" does not want one.
 *
 * Nothing persists this. The state is derived from whether a
 * `ChecklistItemCompletion` exists for the game's adoption, so there is exactly
 * one representation of the fact and no flag that can disagree with it. This
 * enum exists so the derivation has a name, and so the client is handed a
 * value rather than being left to infer meaning from a missing object.
 */
enum ChecklistItemState: string
{
    case Incomplete = 'incomplete';
    case Complete = 'complete';

    /**
     * Read the state from whether a completion exists.
     */
    public static function fromCompletion(bool $completed): self
    {
        return $completed ? self::Complete : self::Incomplete;
    }

    /**
     * Determine whether the requirement has been met.
     */
    public function isComplete(): bool
    {
        return $this === self::Complete;
    }

    /**
     * A human readable label for the state.
     */
    public function label(): string
    {
        return match ($this) {
            self::Incomplete => __('Incomplete'),
            self::Complete => __('Complete'),
        };
    }
}
