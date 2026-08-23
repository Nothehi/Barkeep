<?php

namespace Modules\GameRules\Domain\Exceptions;

use DomainException;

/**
 * A rule about the rules was violated.
 *
 * Named for the *system* rather than for the domain word, because "rule
 * violation" in a module whose central noun is `GameRule` would read as "a
 * player broke a rule of the game" — which is the one thing this module never
 * has an opinion about.
 *
 * Every violation reports the HTTP status it should surface as, so the delivery
 * layer can translate the whole family with one renderer instead of catching
 * each exception individually. That renderer lives in
 * `GameRulesServiceProvider::configureExceptionRendering()`; it is named here in
 * prose rather than in a `@see` tag so the domain keeps no import pointing back
 * up at the layers above it.
 *
 * Worth being clear about what does *not* raise from this family: nothing the
 * validator finds. A phase with no exit, an action with no effect, a victory
 * condition nobody can measure — all of those are findings, reported and never
 * thrown, because a half-written rule system is full of them and a tool that
 * refused to save one would be a tool nobody could start with.
 *
 * The exceptions are the shapes that would make the *stored data* incoherent: a
 * rule that is its own ancestor, a transition crossing into another rule set, an
 * effect belonging to two owners at once. Those are refused on the way in.
 */
abstract class RuleSystemViolation extends DomainException
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
