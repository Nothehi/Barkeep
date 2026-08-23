<?php

namespace Modules\GameRules\Domain\Enums;

use Modules\GameRules\Domain\Enums\Contracts\Described;

/**
 * How seriously to take something the validator found.
 *
 * Two levels, exactly as section 30 of the brief specifies. An error is a shape
 * that cannot be a working rule system under any reading — a rule that is its
 * own ancestor, a transition pointing into another rule set. A warning is
 * something a designer might have meant, or might simply not have got to yet.
 *
 * Neither refuses a save, and that is the important half. A rule set is written
 * over weeks: for most of that time it has no victory condition, half its phases
 * have no exit and several actions have no phase. A validator that blocked on
 * any of that would be a validator nobody could start with, so it reports and
 * the designer decides.
 *
 * The one place the distinction bites is activation. Putting a rule set into
 * play with errors outstanding is refused, because "these are the rules now" is
 * a claim that a rule which is its own ancestor makes false.
 */
enum ValidationSeverity: string implements Described
{
    case Warning = 'warning';
    case Error = 'error';

    /**
     * Determine whether this is a shape that cannot work.
     */
    public function isError(): bool
    {
        return $this === self::Error;
    }

    /**
     * The order findings are listed in, worst first.
     */
    public function weight(): int
    {
        return match ($this) {
            self::Error => 0,
            self::Warning => 1,
        };
    }

    /**
     * A human readable label for the severity.
     */
    public function label(): string
    {
        return match ($this) {
            self::Warning => __('Warning'),
            self::Error => __('Error'),
        };
    }

    /**
     * What the severity means for the designer.
     */
    public function description(): string
    {
        return match ($this) {
            self::Warning => __('Something a designer might have meant, or might not have got to yet.'),
            self::Error => __('A shape that cannot be a working rule system under any reading.'),
        };
    }
}
