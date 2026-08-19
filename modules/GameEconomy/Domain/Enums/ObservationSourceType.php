<?php

namespace Modules\GameEconomy\Domain\Enums;

use Modules\GameEconomy\Domain\Enums\Contracts\Described;

/**
 * Where a balance observation came from.
 *
 * The field that keeps this module honest about what it knows. "Wood becomes
 * unlimited after round six" is a very different claim when it comes from four
 * recorded playtests than when it comes from somebody reading the flow table —
 * both are worth filing, and a record that could not tell them apart would let a
 * suspicion harden into a fact.
 *
 * `Playtest` and `Session` name evidence that lives in another bounded context.
 * The reference beside them is a plain string rather than a foreign key, on
 * purpose: this module never holds a copy of Playtesting's records.
 */
enum ObservationSourceType: string implements Described
{
    case Playtest = 'playtest';
    case Session = 'session';
    case Calculation = 'calculation';
    case Simulation = 'simulation';
    case Review = 'review';
    case Other = 'other';

    /**
     * The source assumed when nobody chose one.
     */
    public static function default(): self
    {
        return self::Other;
    }

    /**
     * Determine whether this kind of source normally points at a record
     * somewhere else.
     *
     * Read by the form, to decide whether to ask for a reference.
     */
    public function expectsReference(): bool
    {
        return match ($this) {
            self::Playtest, self::Session => true,
            self::Calculation, self::Simulation, self::Review, self::Other => false,
        };
    }

    /**
     * Determine whether this source is evidence from a table rather than from a
     * desk.
     */
    public function isEmpirical(): bool
    {
        return match ($this) {
            self::Playtest, self::Session => true,
            self::Calculation, self::Simulation, self::Review, self::Other => false,
        };
    }

    /**
     * A human readable label for the source.
     */
    public function label(): string
    {
        return match ($this) {
            self::Playtest => __('Playtest'),
            self::Session => __('Session'),
            self::Calculation => __('Calculation'),
            self::Simulation => __('Simulation'),
            self::Review => __('Review'),
            self::Other => __('Other'),
        };
    }

    /**
     * What filing something against this source means.
     */
    public function description(): string
    {
        return match ($this) {
            self::Playtest => __('Seen across a whole playtest.'),
            self::Session => __('Seen in one session at the table.'),
            self::Calculation => __('Worked out from the numbers rather than observed.'),
            self::Simulation => __('Produced by running the economy outside the game.'),
            self::Review => __('Noticed by somebody reading through the design.'),
            self::Other => __('Anything that does not fit the sources above.'),
        };
    }
}
