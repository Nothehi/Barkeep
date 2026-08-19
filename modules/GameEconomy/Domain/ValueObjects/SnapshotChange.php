<?php

namespace Modules\GameEconomy\Domain\ValueObjects;

use Modules\GameEconomy\Domain\Enums\BalanceEntityType;
use Modules\GameEconomy\Domain\Enums\SnapshotChangeType;

/**
 * What happened to one record between two snapshots.
 *
 * Records are matched by slug rather than by id, which is the decision that
 * makes a comparison read the way a designer thinks. A resource deleted and
 * recreated under the same name is, to the person asking "what changed between
 * v1.0 and v1.2?", the same resource — and matching on ids would report it as a
 * removal and an addition, burying the actual change in noise.
 *
 * Flows and effects have no slug, so they are matched on the pair that
 * identifies them to a reader: the resource and the name for a flow, the target
 * for an effect.
 */
final readonly class SnapshotChange
{
    /**
     * @param  list<FieldChange>  $fields
     */
    public function __construct(
        public SnapshotChangeType $type,
        public BalanceEntityType $entityType,
        public string $key,
        public string $label,
        public array $fields = [],
    ) {}

    /**
     * A record that is in the later snapshot and not in the earlier one.
     */
    public static function added(BalanceEntityType $entityType, string $key, string $label): self
    {
        return new self(SnapshotChangeType::Added, $entityType, $key, $label);
    }

    /**
     * A record that was in the earlier snapshot and is gone from the later one.
     */
    public static function removed(BalanceEntityType $entityType, string $key, string $label): self
    {
        return new self(SnapshotChangeType::Removed, $entityType, $key, $label);
    }

    /**
     * A record present in both, with at least one field reading differently.
     *
     * @param  list<FieldChange>  $fields
     */
    public static function changed(BalanceEntityType $entityType, string $key, string $label, array $fields): self
    {
        return new self(SnapshotChangeType::Changed, $entityType, $key, $label, $fields);
    }
}
