<?php

use Modules\PrototypeIteration\Domain\Enums\IterationOutcome;

/**
 * There is deliberately no `default()` on this enum, unlike every other in the module. An outcome that fell
 * back to something would record the platform's guess as the studio's own judgement, so completion requires
 * one to be chosen.
 */
it('has no default, because nobody may guess how a cycle went', function () {
    expect(method_exists(IterationOutcome::class, 'default'))->toBeFalse();
});

it('offers exactly four readings of a cycle', function () {
    expect(IterationOutcome::cases())->toBe([
        IterationOutcome::Success,
        IterationOutcome::Partial,
        IterationOutcome::Failed,
        IterationOutcome::Inconclusive,
    ]);
});

/**
 * Inconclusive is a first-class answer rather than a failure. A cycle that did not settle its question has
 * still told the designer something, and forcing it into "failed" would make the history lie.
 */
it('keeps inconclusive separate from failed', function () {
    expect(IterationOutcome::Inconclusive)->not->toBe(IterationOutcome::Failed)
        ->and(IterationOutcome::Failed->description())->not->toBe(IterationOutcome::Inconclusive->description());
});

it('words and explains every outcome, because these are picked in a hurry', function (IterationOutcome $outcome) {
    expect($outcome->label())->not->toBeEmpty()
        ->and($outcome->description())->not->toBeEmpty();
})->with(IterationOutcome::cases());
