<?php

namespace Modules\GameEconomy\Domain\Enums;

use Modules\GameEconomy\Domain\Enums\Contracts\Described;

/**
 * How much the studio actually believes an assumption.
 *
 * Three levels, which is as many as anybody uses honestly. The field exists so
 * that a belief can be written down before it is proved — an assumption held
 * with low confidence is a thing to go and test, and a table without this column
 * would flatten every hunch into an assertion and make the whole record read as
 * more settled than it is.
 */
enum AssumptionConfidence: string implements Described
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    /**
     * The confidence assumed when nobody chose one.
     *
     * Medium rather than low, because somebody bothering to write an assumption
     * down usually believes it — and a default of low would make the whole list
     * read as guesswork the first time anybody skipped the field.
     */
    public static function default(): self
    {
        return self::Medium;
    }

    /**
     * How strongly held this is, as a number, so lists can be ordered by it.
     */
    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
        };
    }

    /**
     * Determine whether this assumption is one somebody should go and test.
     */
    public function needsEvidence(): bool
    {
        return $this === self::Low;
    }

    /**
     * A human readable label for the confidence.
     */
    public function label(): string
    {
        return match ($this) {
            self::Low => __('Low confidence'),
            self::Medium => __('Medium confidence'),
            self::High => __('High confidence'),
        };
    }

    /**
     * What holding an assumption at this level means.
     */
    public function description(): string
    {
        return match ($this) {
            self::Low => __('A hunch. Worth testing before anything is built on it.'),
            self::Medium => __('Reasoned, but not yet demonstrated at a table.'),
            self::High => __('Backed by evidence the studio trusts.'),
        };
    }
}
