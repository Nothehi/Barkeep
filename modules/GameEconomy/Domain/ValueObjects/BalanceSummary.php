<?php

namespace Modules\GameEconomy\Domain\ValueObjects;

/**
 * The headline figures of a balance profile.
 *
 * What the dashboard reads: eight resources, fourteen actions, twenty-seven
 * variables, three warnings, one error. Counted at the moment of the request
 * rather than kept on the profile, because a count kept anywhere is a count that
 * can be wrong, and these are cheap.
 *
 * Warnings and errors are separated rather than reported as one number with a
 * severity breakdown behind it, because those are the two figures somebody acts
 * on differently: an error means the economy cannot work as configured, and one
 * of them is worth more attention than a dozen warnings.
 */
final readonly class BalanceSummary
{
    public function __construct(
        public int $resources,
        public int $flows,
        public int $actions,
        public int $costs,
        public int $rewards,
        public int $effects,
        public int $variables,
        public int $scenarios,
        public int $assumptions,
        public int $observations,
        public int $warnings,
        public int $errors,
    ) {}

    /**
     * Determine whether the profile has anything in it yet.
     */
    public function isEmpty(): bool
    {
        return $this->resources === 0 && $this->actions === 0 && $this->variables === 0;
    }

    /**
     * Determine whether the analysis found anything that cannot work.
     */
    public function hasErrors(): bool
    {
        return $this->errors > 0;
    }

    /**
     * Determine whether the analysis found anything at all.
     */
    public function hasFindings(): bool
    {
        return $this->warnings > 0 || $this->errors > 0;
    }
}
