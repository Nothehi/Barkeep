<?php

namespace Modules\GameEconomy\Domain\Events;

/**
 * Dispatched when a scenario states a value differently, or stops doing so.
 *
 * One event for setting and clearing rather than two, because from outside the
 * module they are the same fact: what this scenario says about this variable has
 * changed. `wasRemoved` distinguishes them for a consumer that cares.
 *
 * Note what this event does *not* mean. Nothing about the base variable moved —
 * a scenario's values live in their own table, and no path exists on which
 * setting one writes to the profile's own number.
 */
final readonly class ScenarioVariableChanged
{
    public function __construct(
        public string $scenarioId,
        public string $profileId,
        public string $variableId,
        public bool $wasRemoved,
    ) {}
}
