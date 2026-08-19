<?php

namespace Modules\GameEconomy\Domain\Enums;

use Modules\GameEconomy\Domain\Enums\Contracts\Described;

/**
 * How badly a balance observation reflects on the economy.
 *
 * Five levels rather than the three the warnings use, because these are written
 * by a person about a real game rather than derived by a rule. "Wood is slightly
 * plentiful" and "wood is infinite from round six" are both true observations
 * about the same resource and belong at different ends of a list somebody triages
 * on a Monday morning.
 */
enum ObservationSeverity: string implements Described
{
    case Info = 'info';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    /**
     * The severity assumed when nobody chose one.
     */
    public static function default(): self
    {
        return self::Info;
    }

    /**
     * How serious this is, as a number, so lists can be ordered by it.
     */
    public function weight(): int
    {
        return match ($this) {
            self::Info => 0,
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Critical => 4,
        };
    }

    /**
     * Determine whether this observation is one somebody has to act on.
     */
    public function demandsAction(): bool
    {
        return $this->weight() >= self::High->weight();
    }

    /**
     * A human readable label for the severity.
     */
    public function label(): string
    {
        return match ($this) {
            self::Info => __('Note'),
            self::Low => __('Low'),
            self::Medium => __('Medium'),
            self::High => __('High'),
            self::Critical => __('Critical'),
        };
    }

    /**
     * What filing something at this level means.
     */
    public function description(): string
    {
        return match ($this) {
            self::Info => __('Worth writing down. Nothing is wrong.'),
            self::Low => __('A rough edge somebody noticed.'),
            self::Medium => __('The economy is off, but the game still works.'),
            self::High => __('Players are being pushed towards one strategy.'),
            self::Critical => __('The economy is broken and the game cannot be played as intended.'),
        };
    }
}
