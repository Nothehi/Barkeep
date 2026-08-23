<?php

namespace Modules\GameRules\Domain\Enums\Contracts;

/**
 * An enum that knows how it should read on screen.
 *
 * Every vocabulary in this module words itself, so a rule type renamed in the
 * domain reads the new way in the interface without anything in TypeScript
 * changing. This interface is what lets the code that *builds* those option
 * lists say so once instead of once per enum.
 *
 * Implemented only by backed enums, so a parameter that accepts one is typed
 * `Labelled&\BackedEnum` — the interface says how it reads, and `BackedEnum`
 * says it has a value to send.
 */
interface Labelled
{
    /**
     * A human readable label for the case.
     */
    public function label(): string;
}
