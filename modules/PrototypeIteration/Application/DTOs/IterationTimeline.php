<?php

namespace Modules\PrototypeIteration\Application\DTOs;

use Illuminate\Support\Collection;
use Modules\PrototypeIteration\Domain\Enums\TimelineEntryKind;
use Modules\PrototypeIteration\Domain\Models\Iteration;

/**
 * A design cycle as it happened, in order.
 *
 * The shape section 41 asks for: one axis, everything on it, so a reader can see
 * that the decision came four days after the playtest and two hours before the
 * cycle closed. Assembled on read from the records that already exist — there is
 * no timeline table, no event log and no denormalised feed, because the moment a
 * stored sequence and the rows it describes can disagree, somebody spends an
 * afternoon finding out which one is lying.
 *
 * It is affordable because a cycle is small: a handful of changes, a couple of
 * experiments, a few playtests, one or two decisions. When somebody eventually
 * wants a feed across a whole studio, that is a reporting problem for the
 * analytics capability rather than a reason to materialise this.
 *
 * Nothing here interprets anything. The timeline shows what was recorded and in
 * what order; whether that sequence was good practice is a judgement the platform
 * is in no position to make.
 */
final readonly class IterationTimeline
{
    /**
     * @param  Collection<int, TimelineEntry>  $entries
     */
    public function __construct(
        public Iteration $iteration,
        public Collection $entries,
    ) {}

    /**
     * Build a timeline from entries in whatever order they were gathered.
     *
     * Sorting happens here rather than at each source, so a source added later
     * cannot forget to order itself. The key is a string rather than a timestamp
     * so the sort stays total — see {@see TimelineEntry::sortKey()}.
     *
     * @param  iterable<int, TimelineEntry>  $entries
     */
    public static function of(Iteration $iteration, iterable $entries): self
    {
        return new self(
            iteration: $iteration,
            entries: Collection::make($entries)
                ->sortBy(fn (TimelineEntry $entry): string => $entry->sortKey())
                ->values(),
        );
    }

    /**
     * The timeline of a cycle nothing has been recorded against yet.
     */
    public static function empty(Iteration $iteration): self
    {
        return new self($iteration, Collection::make());
    }

    /**
     * Determine whether anything has happened yet.
     *
     * A planned iteration answers false even though it exists, because it has no
     * start entry — which is what lets the screen offer "start this iteration"
     * instead of drawing an empty axis.
     */
    public function isEmpty(): bool
    {
        return $this->entries->isEmpty();
    }

    /**
     * How many entries of each kind the cycle produced.
     *
     * @return array<string, int>
     */
    public function tally(): array
    {
        $tally = array_fill_keys(
            array_map(fn (TimelineEntryKind $kind): string => $kind->value, TimelineEntryKind::cases()),
            0,
        );

        foreach ($this->entries as $entry) {
            $tally[$entry->kind->value]++;
        }

        return $tally;
    }
}
