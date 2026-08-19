<?php

namespace Modules\GameEconomy\Domain\ValueObjects;

/**
 * One field that reads differently in two snapshots.
 *
 * "Starting Gold: 10 → 12" is one of these. Both sides are carried as strings
 * because a comparison spans every kind of field a configuration has — a number,
 * a name, a category, a boolean — and rendering them all the same way is what
 * lets the interface draw one list rather than six.
 *
 * Amounts are stringified through {@see Quantity::label()} on the way in, so 10
 * reads as 10 rather than 10.000000 and the diff shows the change somebody made
 * rather than the precision the column keeps.
 */
final readonly class FieldChange
{
    public function __construct(
        public string $field,
        public string $label,
        public ?string $before,
        public ?string $after,
    ) {}

    /**
     * Determine whether the two sides actually differ.
     *
     * Asked rather than assumed, because the comparison builds a candidate for
     * every field it knows about and keeps only the ones that moved.
     */
    public function isDifferent(): bool
    {
        return $this->before !== $this->after;
    }
}
