<?php

use Modules\GameRules\Infrastructure\Analysis\CycleDetector;

/*
 * Four things in this module can loop and all four are the same problem, which is
 * why there is one detector. These tests exercise it on plain arrays of ids, with
 * no database and no models — which is the point of it taking maps rather than
 * collections.
 */

beforeEach(function () {
    $this->cycles = new CycleDetector;
});

it('allows a node to be promoted to the top level', function () {
    expect($this->cycles->wouldCreateCycle(['a' => null, 'b' => 'a'], 'b', null))->toBeFalse();
});

it('refuses a node as its own parent', function () {
    expect($this->cycles->wouldCreateCycle(['a' => null], 'a', 'a'))->toBeTrue();
});

it('refuses a reparent that closes a loop several levels up', function () {
    $parents = ['a' => null, 'b' => 'a', 'c' => 'b', 'd' => 'c'];

    expect($this->cycles->wouldCreateCycle($parents, 'a', 'd'))->toBeTrue()
        ->and($this->cycles->wouldCreateCycle($parents, 'a', 'b'))->toBeTrue();
});

it('allows a reparent that keeps the tree a tree', function () {
    $parents = ['a' => null, 'b' => null, 'c' => 'b'];

    expect($this->cycles->wouldCreateCycle($parents, 'c', 'a'))->toBeFalse();
});

it('refuses attaching to a branch that already loops', function () {
    /*
     * The new edge is not what breaks it, but attaching to a broken branch would
     * hide the original problem under a second one.
     */
    $parents = ['a' => 'b', 'b' => 'a', 'c' => null];

    expect($this->cycles->wouldCreateCycle($parents, 'c', 'a'))->toBeTrue();
});

it('finds the nodes in a hierarchy that are their own ancestors', function () {
    $parents = ['a' => 'b', 'b' => 'a', 'c' => null, 'd' => 'c'];

    expect($this->cycles->findLoopingNodes($parents))->toEqualCanonicalizing(['a', 'b']);
});

it('finds nothing in a hierarchy that terminates', function () {
    expect($this->cycles->findLoopingNodes(['a' => null, 'b' => 'a', 'c' => 'b']))->toBe([]);
});

it('refuses a directed edge that would close a loop', function () {
    $edges = ['a' => ['b'], 'b' => ['c']];

    expect($this->cycles->wouldCloseLoop($edges, 'c', 'a'))->toBeTrue()
        ->and($this->cycles->wouldCloseLoop($edges, 'a', 'a'))->toBeTrue()
        ->and($this->cycles->wouldCloseLoop($edges, 'a', 'd'))->toBeFalse();
});

it('finds the nodes taking part in a directed cycle', function () {
    $edges = ['a' => ['b'], 'b' => ['c'], 'c' => ['a'], 'd' => ['a']];

    expect($this->cycles->findLoopingEdges($edges))->toEqualCanonicalizing(['a', 'b', 'c']);
});

it('walks the reachable part of a graph', function () {
    $edges = ['start' => ['a'], 'a' => ['b'], 'b' => ['a'], 'orphan' => ['x']];

    expect($this->cycles->reachableFrom($edges, ['start']))->toEqualCanonicalizing(['start', 'a', 'b']);
});

it('terminates on a graph that already loops', function () {
    $edges = ['a' => ['b'], 'b' => ['a']];

    expect($this->cycles->reachableFrom($edges, ['a']))->toEqualCanonicalizing(['a', 'b']);
});
