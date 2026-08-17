<?php

namespace Modules\DesignFramework\Domain\Exceptions;

/**
 * Raised when something tries to place framework content at an impossible
 * position.
 *
 * Positions are 1-based and contiguous. A caller asking for position 0, or for
 * a position past the end of the list, is refused rather than clamped — a
 * silent clamp is how a drag that landed in the wrong place becomes a reorder
 * nobody intended.
 */
final class InvalidPosition extends FrameworkRuleViolation
{
    public static function forValue(int $value): self
    {
        return new self(__('Positions start at one; :value is not a position.', ['value' => $value]));
    }

    public static function beyondEnd(int $value, int $count): self
    {
        return new self(__('There are only :count items to order, so :value is not a position.', [
            'count' => $count,
            'value' => $value,
        ]));
    }

    public function field(): string
    {
        return 'position';
    }
}
