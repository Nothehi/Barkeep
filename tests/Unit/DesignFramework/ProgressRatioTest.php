<?php

use Modules\DesignFramework\Domain\ValueObjects\ProgressRatio;

it('reports a fraction as completed out of total', function () {
    $ratio = ProgressRatio::of(3, 4);

    expect($ratio->completed)->toBe(3)
        ->and($ratio->total)->toBe(4)
        ->and($ratio->remaining())->toBe(1)
        ->and((string) $ratio)->toBe('3 / 4');
});

/**
 * An empty total is 0%, not a division by zero and not 100%.
 *
 * A phase with no criteria has not been assessed, and claiming otherwise would let a framework author
 * raise everybody's progress by deleting content.
 */
it('reports nothing to do as nothing done', function () {
    $ratio = ProgressRatio::none();

    expect($ratio->percentage())->toBe(0)
        ->and($ratio->isEmpty())->toBeTrue()
        ->and($ratio->isComplete())->toBeFalse();
});

/**
 * Being told a phase is complete while one checklist item is outstanding is the single most annoying way
 * a progress bar can lie.
 */
it('never rounds a partial result up to a hundred', function () {
    expect(ProgressRatio::of(999, 1000)->percentage())->toBe(99);
});

it('reports a finished set as a hundred', function () {
    expect(ProgressRatio::of(4, 4)->percentage())->toBe(100)
        ->and(ProgressRatio::of(4, 4)->isComplete())->toBeTrue();
});

it('rounds down rather than to nearest', function () {
    expect(ProgressRatio::of(2, 3)->percentage())->toBe(66);
});

it('caps a completed count that overshoots its total', function () {
    expect(ProgressRatio::of(9, 4)->completed)->toBe(4)
        ->and(ProgressRatio::of(9, 4)->percentage())->toBe(100);
});

it('refuses to report a negative total', function () {
    expect(ProgressRatio::of(-1, -5)->total)->toBe(0);
});

/**
 * Summing the pairs rather than averaging the percentages is what makes a phase with one criterion and
 * twenty checklist items weight them by how much work each represents.
 */
it('adds ratios by summing both halves', function () {
    $combined = ProgressRatio::of(1, 1)->plus(ProgressRatio::of(0, 19));

    expect($combined->completed)->toBe(1)
        ->and($combined->total)->toBe(20)
        ->and($combined->percentage())->toBe(5);
});
