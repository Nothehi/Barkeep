<?php

namespace Modules\DesignFramework\Application\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\DesignFramework\Domain\ValueObjects\Position;

/**
 * The one place framework content is ordered.
 *
 * Section 17 asks for ordering logic to be centralised, and this is the whole of
 * it. Phases, principles, criteria, practices, prompts, checklists and checklist
 * items are all ordered by an explicit `position`, and every insertion, move and
 * removal in the module goes through the three methods below. Nothing else writes
 * a position.
 *
 * ## Why positions are rewritten rather than nudged
 *
 * The tempting implementation of "move item 5 to position 2" is to shift the
 * affected range by one with a single `UPDATE ... SET position = position + 1
 * WHERE position BETWEEN 2 AND 4`. It is fewer queries, and it goes wrong the
 * first time the list is not contiguous — which happens as soon as anything is
 * deleted, imported, or written by a migration that did not know about this class.
 *
 * So instead the sibling set is read in order, the subject is spliced into place,
 * and positions are written back as 1..n. That makes every reorder
 * self-healing: a list that had gaps or duplicates comes out contiguous, and the
 * cost is a handful of updates over a set that is a dozen rows in practice and
 * bounded by how much content a human will author in one phase.
 *
 * It is also why the `position` columns carry no unique index. Rewriting a run of
 * rows passes through states where two share a position, and a unique constraint
 * would refuse them unless it were deferrable — which SQLite, the test database,
 * does not support. The invariant is maintained here, inside a transaction,
 * rather than asserted by an index that would only be enforceable on one of the
 * two databases this project runs on.
 *
 * ## What the caller supplies
 *
 * A query describing the sibling set — "this version's phases", "this phase's
 * criteria", "this checklist's items". That query *is* the definition of "among
 * its siblings", and keeping it at the call site is what lets content filed under
 * no phase order independently of content filed under one, without this class
 * knowing that phases exist.
 */
final class ContentSequencer
{
    /**
     * The position a new row appended to the given set should take.
     *
     * Counted rather than derived from the maximum position, so a set that has
     * somehow grown gaps still produces a sensible next value instead of drifting
     * further from contiguous. The next {@see move()} on the set repairs it
     * properly.
     *
     * @param  Builder<covariant Model>  $siblings  the set the row is joining, not yet including it
     */
    public function append(Builder $siblings): int
    {
        return Position::afterCount((clone $siblings)->count())->value;
    }

    /**
     * Move a row to a requested position, rewriting the set around it.
     *
     * The requested position is validated against the set's actual size rather
     * than clamped: asking for position 9 in a list of four is a mistake worth
     * reporting, because silently landing at the end is how a mis-dragged item
     * becomes a reorder nobody intended.
     *
     * Returns the position actually taken, which equals the request whenever this
     * does not raise — the return value exists so callers can report it without
     * re-reading the row.
     *
     * @param  Builder<covariant Model>  $siblings  the set including the subject
     * @return int the position the subject now holds
     */
    public function move(Model $subject, Builder $siblings, int $requested): int
    {
        return DB::transaction(function () use ($subject, $siblings, $requested): int {
            $rows = $this->ordered($siblings);

            $position = Position::within($requested, $rows->count());

            /*
             * Rebuild the list with the subject at its new index. The subject is
             * taken out by key and put back as the *caller's* instance, so its
             * in-memory position matches what is written and the caller can render
             * it without reloading.
             */
            $reordered = $rows
                ->reject(fn (Model $row): bool => $row->getKey() === $subject->getKey())
                ->values()
                ->all();

            array_splice($reordered, $position->value - 1, 0, [$subject]);

            $this->write($reordered);

            return $position->value;
        });
    }

    /**
     * Close any gaps in a set's positions.
     *
     * Called after something is removed from a set, and after content is moved
     * between phases. Idempotent, and safe to call on a set that is already
     * contiguous — it writes nothing in that case.
     *
     * @param  Builder<covariant Model>  $siblings
     */
    public function resequence(Builder $siblings): void
    {
        DB::transaction(function () use ($siblings): void {
            $this->write($this->ordered($siblings)->all());
        });
    }

    /**
     * Read a sibling set in the order the domain says it is in.
     *
     * `created_at` is the tiebreaker rather than the primary key, so a set that
     * arrives with duplicate positions comes out in the order it was written
     * rather than in the order uuids happened to sort.
     *
     * @param  Builder<covariant Model>  $siblings
     * @return Collection<int, Model>
     */
    private function ordered(Builder $siblings): Collection
    {
        return (clone $siblings)
            ->orderBy('position')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Write positions 1..n across the given rows, skipping the ones already right.
     *
     * `forceFill` rather than `fill`, because `position` is deliberately not
     * fillable on any of these models: it is allocated by this class and must not
     * be settable from a request body.
     *
     * @param  array<int, Model>  $rows
     */
    private function write(array $rows): void
    {
        $position = 0;

        foreach ($rows as $row) {
            $position++;

            if ($row->getAttribute('position') === $position) {
                continue;
            }

            $row->forceFill(['position' => $position])->save();
        }
    }
}
