<?php

namespace Modules\GameEconomy\Domain\Exceptions;

use DomainException;

/**
 * A balance invariant was violated.
 *
 * Every rule the module enforces reports the HTTP status it should surface as,
 * so the delivery layer can translate the whole family with one renderer instead
 * of catching each exception individually. That renderer lives in
 * `GameEconomyServiceProvider::configureExceptionRendering()`; it is named here
 * in prose rather than in a `@see` tag so the domain keeps no import pointing
 * back up at the layers above it.
 *
 * Worth being clear about what does *not* raise from this family: nothing the
 * analysis finds. A resource with no source, an action that costs nothing, a
 * variable outside its range — all of those are findings, reported and never
 * thrown, because a half-built economy is full of them and a tool that refused
 * to save one would be a tool nobody could start with.
 */
abstract class EconomyRuleViolation extends DomainException
{
    /**
     * The HTTP status this violation should be reported as.
     */
    public function status(): int
    {
        return 422;
    }

    /**
     * The input field the violation should be attributed to, if any.
     *
     * When set, the violation is surfaced as a validation error so forms can
     * show it next to the field that caused it.
     */
    public function field(): ?string
    {
        return null;
    }
}
