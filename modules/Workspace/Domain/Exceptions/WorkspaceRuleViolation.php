<?php

namespace Modules\Workspace\Domain\Exceptions;

use DomainException;

/**
 * A workspace invariant was violated.
 *
 * Every rule the domain enforces reports the HTTP status it should surface as,
 * so the delivery layer can translate the whole family with one renderer
 * instead of catching each exception individually. That renderer lives in
 * `WorkspaceServiceProvider::configureExceptionRendering()`; it is named here
 * in prose rather than in a `@see` tag so the domain keeps no import pointing
 * back up at the layers above it.
 */
abstract class WorkspaceRuleViolation extends DomainException
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
