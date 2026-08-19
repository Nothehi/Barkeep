<?php

namespace Modules\GameEconomy\Application\Queries;

use Modules\GameEconomy\Domain\Exceptions\SnapshotsAreNotComparable;
use Modules\GameEconomy\Domain\Models\BalanceSnapshot;
use Modules\GameEconomy\Domain\ValueObjects\SnapshotComparison;
use Modules\GameEconomy\Infrastructure\Calculations\SnapshotComparator;

/**
 * What changed between two frozen configurations.
 *
 * Both refusals here are worth making loudly rather than returning an empty
 * diff. Comparing a snapshot with itself produces "nothing changed", which is
 * true, useless, and reads as a working comparison. Comparing snapshots of two
 * different profiles produces a diff in which everything was removed and
 * everything was added, which is worse — it looks like a catastrophic edit.
 *
 * The order is not corrected. `from` is the earlier snapshot because the caller
 * said so, and silently swapping them would make "+2" mean whichever direction
 * the module guessed.
 */
final class CompareBalanceSnapshots
{
    public function __construct(private readonly SnapshotComparator $comparator) {}

    public function handle(BalanceSnapshot $from, BalanceSnapshot $to): SnapshotComparison
    {
        if ($from->getKey() === $to->getKey()) {
            throw SnapshotsAreNotComparable::becauseTheyAreTheSame();
        }

        if ($from->balance_profile_id !== $to->balance_profile_id) {
            throw SnapshotsAreNotComparable::becauseTheyBelongToDifferentProfiles();
        }

        return $this->comparator->compare($from, $to);
    }
}
