<?php

namespace Modules\GameEconomy\Domain\Enums;

use Modules\GameEconomy\Domain\Enums\Contracts\Labelled;

/**
 * What happened to one record between two snapshots.
 *
 * Three cases, which is all a diff has. They are named rather than inferred from
 * the presence of a before or an after value, so that a comparison can be read
 * without reconstructing the logic that produced it — "removed" is a statement,
 * where "after is null" is a puzzle.
 */
enum SnapshotChangeType: string implements Labelled
{
    case Added = 'added';
    case Removed = 'removed';
    case Changed = 'changed';

    /**
     * A human readable label for the change.
     */
    public function label(): string
    {
        return match ($this) {
            self::Added => __('Added'),
            self::Removed => __('Removed'),
            self::Changed => __('Changed'),
        };
    }
}
