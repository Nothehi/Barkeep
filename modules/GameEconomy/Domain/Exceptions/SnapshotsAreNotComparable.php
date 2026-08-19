<?php

namespace Modules\GameEconomy\Domain\Exceptions;

/**
 * Raised when a comparison is asked for something it cannot diff.
 *
 * Two cases, and both are worth refusing loudly rather than returning an empty
 * diff. Comparing a snapshot with itself produces "nothing changed", which is
 * true and useless and reads as a working comparison. Comparing snapshots of two
 * different profiles produces a diff in which everything was removed and
 * everything was added, which is worse: it looks like a catastrophic edit.
 */
final class SnapshotsAreNotComparable extends EconomyRuleViolation
{
    public static function becauseTheyAreTheSame(): self
    {
        return new self(__('Choose two different snapshots to compare.'));
    }

    public static function becauseTheyBelongToDifferentProfiles(): self
    {
        return new self(__('Snapshots can only be compared within the same balance profile.'));
    }

    public function field(): string
    {
        return 'to';
    }
}
