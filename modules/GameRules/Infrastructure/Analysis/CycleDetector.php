<?php

namespace Modules\GameRules\Infrastructure\Analysis;

/**
 * The one implementation of "does following these pointers come back here?".
 *
 * Four things in this module can loop and all four are the same problem: the
 * rule hierarchy, the phase hierarchy, the rule reference graph and — in the
 * shape the validator cares about — the phase transition graph. Section 54 of
 * the brief asks for cycle detection on each; writing it four times would be
 * four chances to get the termination condition wrong.
 *
 * Two shapes of input, because the data has two shapes. A hierarchy is a map of
 * child to single parent; a reference graph is a map of node to several
 * successors. Both are plain arrays of ids: nothing here loads a model, which is
 * what lets the whole check run on one `pluck` rather than on the rulebook.
 *
 * ## Why both `wouldCreateCycle` and `findCycles` exist
 *
 * They answer different questions at different moments. `wouldCreateCycle` is
 * asked *before* a write, so a command can refuse the edge that would close the
 * loop — which is the only point at which the refusal names something the caller
 * just did. `findCycles` is asked afterwards by the validator, and catches loops
 * that predate the check: data restored from a backup, or a clone of a set
 * written before this file existed. Neither makes the other redundant.
 *
 * Every walk is bounded by the number of nodes, so a loop already in the data
 * cannot make the detector itself hang.
 */
final class CycleDetector
{
    /**
     * Determine whether giving a node a new parent would make it its own
     * ancestor.
     *
     * Walks up from the proposed parent looking for the node. Reparenting to
     * null — promoting to the top level — can never cycle and is answered
     * immediately.
     *
     * @param  array<string, string|null>  $parents  child id => parent id
     */
    public function wouldCreateCycle(array $parents, string $nodeId, ?string $parentId): bool
    {
        if ($parentId === null) {
            return false;
        }

        if ($parentId === $nodeId) {
            return true;
        }

        $seen = [];
        $current = $parentId;
        $limit = count($parents) + 1;

        while ($current !== null && $limit-- > 0) {
            if ($current === $nodeId) {
                return true;
            }

            if (isset($seen[$current])) {
                /*
                 * The existing data already loops above the proposed parent. The
                 * new edge is not what breaks it, but attaching to a broken
                 * branch would hide the original problem under a second one, so
                 * it is refused too and the validator reports the real cause.
                 */
                return true;
            }

            $seen[$current] = true;
            $current = $parents[$current] ?? null;
        }

        return false;
    }

    /**
     * Every node in a hierarchy that is its own ancestor.
     *
     * Returns the ids rather than the cycles themselves: the validator reports
     * one finding per rule, naming that rule, which is what somebody needs to
     * click on. Which other rules are in the loop with it is not actionable —
     * breaking any edge fixes it.
     *
     * @param  array<string, string|null>  $parents  child id => parent id
     * @return list<string>
     */
    public function findLoopingNodes(array $parents): array
    {
        $looping = [];

        foreach (array_keys($parents) as $nodeId) {
            $seen = [];
            $current = $parents[$nodeId] ?? null;
            $limit = count($parents) + 1;

            while ($current !== null && $limit-- > 0) {
                if ($current === $nodeId) {
                    $looping[] = $nodeId;

                    break;
                }

                if (isset($seen[$current])) {
                    break;
                }

                $seen[$current] = true;
                $current = $parents[$current] ?? null;
            }
        }

        return $looping;
    }

    /**
     * Determine whether adding a directed edge would close a loop.
     *
     * Asked before a rule reference is written. The edge `from → to` closes a
     * loop exactly when `to` can already reach `from`, so this is a reachability
     * question rather than a cycle one — which is why it is cheap even on a
     * densely cross-referenced rulebook.
     *
     * @param  array<string, list<string>>  $edges  node id => successor ids
     */
    public function wouldCloseLoop(array $edges, string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return $this->reaches($edges, $to, $from);
    }

    /**
     * Every node that takes part in a directed cycle.
     *
     * A node is in a cycle when it can reach itself. Simple rather than clever —
     * Tarjan's algorithm would find the strongly connected components in one
     * pass, and would be the wrong trade here: a rulebook has tens of references,
     * and the obvious version is the one somebody can check by reading.
     *
     * @param  array<string, list<string>>  $edges  node id => successor ids
     * @return list<string>
     */
    public function findLoopingEdges(array $edges): array
    {
        $looping = [];

        foreach (array_keys($edges) as $nodeId) {
            if ($this->reaches($edges, $nodeId, $nodeId)) {
                $looping[] = $nodeId;
            }
        }

        return $looping;
    }

    /**
     * Every node reachable from the given starting points.
     *
     * What the graph builder uses to work out which phases play never arrives at.
     * Breadth-first, so the walk is bounded by the edges rather than by the call
     * stack.
     *
     * @param  array<string, list<string>>  $edges  node id => successor ids
     * @param  list<string>  $from
     * @return list<string>
     */
    public function reachableFrom(array $edges, array $from): array
    {
        $seen = [];
        $queue = $from;

        while ($queue !== []) {
            $node = array_shift($queue);

            if (isset($seen[$node])) {
                continue;
            }

            $seen[$node] = true;

            foreach ($edges[$node] ?? [] as $next) {
                if (! isset($seen[$next])) {
                    $queue[] = $next;
                }
            }
        }

        return array_keys($seen);
    }

    /**
     * Determine whether one node can reach another by following edges.
     *
     * Note the starting shape: the walk begins at `$from`'s successors rather
     * than at `$from`, so "can a node reach itself" is a question about a real
     * cycle rather than a trivially true one.
     *
     * @param  array<string, list<string>>  $edges
     */
    private function reaches(array $edges, string $from, string $target): bool
    {
        $seen = [];
        $queue = $edges[$from] ?? [];

        while ($queue !== []) {
            $node = array_shift($queue);

            if ($node === $target) {
                return true;
            }

            if (isset($seen[$node])) {
                continue;
            }

            $seen[$node] = true;

            foreach ($edges[$node] ?? [] as $next) {
                $queue[] = $next;
            }
        }

        return false;
    }
}
