<?php

namespace Modules\GameEconomy\Domain\ValueObjects;

/**
 * The difference between two frozen configurations.
 *
 * Grouped by what kind of thing changed rather than returned as one flat list,
 * because that is how the question gets asked: "what happened to the variables
 * between v1.0 and v1.2?" is a different question from "what resources did we
 * add?", and a single list would make both of them a filtering exercise.
 *
 * Direction is explicit and fixed — `from` is the earlier snapshot and `to` the
 * later one — so "+2" always means the number went up. A comparison that let the
 * caller choose the order would make every sign in the result ambiguous.
 */
final readonly class SnapshotComparison
{
    /**
     * @param  list<SnapshotChange>  $resources
     * @param  list<SnapshotChange>  $flows
     * @param  list<SnapshotChange>  $actions
     * @param  list<SnapshotChange>  $costs
     * @param  list<SnapshotChange>  $rewards
     * @param  list<SnapshotChange>  $effects
     * @param  list<SnapshotChange>  $variables
     */
    public function __construct(
        public string $fromSnapshotId,
        public string $fromSnapshotName,
        public string $toSnapshotId,
        public string $toSnapshotName,
        public array $resources,
        public array $flows,
        public array $actions,
        public array $costs,
        public array $rewards,
        public array $effects,
        public array $variables,
    ) {}

    /**
     * Every change, in one list.
     *
     * @return list<SnapshotChange>
     */
    public function all(): array
    {
        return array_merge(
            $this->resources,
            $this->flows,
            $this->actions,
            $this->costs,
            $this->rewards,
            $this->effects,
            $this->variables,
        );
    }

    /**
     * How many things moved.
     */
    public function count(): int
    {
        return count($this->all());
    }

    /**
     * Determine whether the two snapshots describe the same economy.
     */
    public function isIdentical(): bool
    {
        return $this->count() === 0;
    }
}
