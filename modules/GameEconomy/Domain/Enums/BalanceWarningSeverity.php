<?php

namespace Modules\GameEconomy\Domain\Enums;

use Modules\GameEconomy\Domain\Enums\Contracts\Labelled;

/**
 * How seriously to take something the analysis found.
 *
 * Three levels, and the distinction that matters is between the middle and the
 * top. A warning is a shape that is usually a mistake; an error is a shape that
 * cannot be a working economy under any reading — a resource nothing produces
 * cannot be spent, whatever the designer intended.
 *
 * Nothing in this module refuses a save because of an error. The analysis reports
 * and the designer decides, which is section 31 of the brief and the reason a
 * balance tool is usable at all: a half-built economy is full of errors, and a
 * tool that would not let you save one would be a tool nobody could start with.
 */
enum BalanceWarningSeverity: string implements Labelled
{
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';

    /**
     * How serious this is, as a number, so findings can be ordered by it.
     */
    public function weight(): int
    {
        return match ($this) {
            self::Info => 0,
            self::Warning => 1,
            self::Error => 2,
        };
    }

    /**
     * Determine whether this finding describes something that cannot work.
     */
    public function isError(): bool
    {
        return $this === self::Error;
    }

    /**
     * A human readable label for the severity.
     */
    public function label(): string
    {
        return match ($this) {
            self::Info => __('Note'),
            self::Warning => __('Warning'),
            self::Error => __('Error'),
        };
    }
}
