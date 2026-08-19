<?php

use Modules\PrototypeIteration\Domain\Enums\IterationStatus;

it('starts life as planned', function () {
    expect(IterationStatus::default())->toBe(IterationStatus::Planned);
});

it('allows exactly the moves a design cycle can make', function (IterationStatus $from, array $expected) {
    expect($from->transitions())->toBe($expected);
})->with([
    'planned' => [IterationStatus::Planned, [IterationStatus::InProgress, IterationStatus::Cancelled]],
    'in progress' => [IterationStatus::InProgress, [IterationStatus::Completed, IterationStatus::Cancelled]],
    'completed' => [IterationStatus::Completed, []],
    'cancelled' => [IterationStatus::Cancelled, []],
]);

/**
 * The one place this matrix is stricter than the playtest one. A cycle that completed without ever
 * starting would carry an outcome nobody gathered evidence for; the honest ending for work that never
 * happened is cancellation.
 */
it('refuses to complete a cycle that never started', function () {
    expect(IterationStatus::Planned->canTransitionTo(IterationStatus::Completed))->toBeFalse();
});

/**
 * The refusal that matters most. An iteration's outcome is the sentence the next iteration is built on, so
 * reopening one would make the design history a record of what somebody currently believes rather than of
 * what they decided at the time.
 */
it('treats both endings as final', function (IterationStatus $status) {
    expect($status->isTerminal())->toBeTrue()
        ->and($status->canTransitionTo(IterationStatus::InProgress))->toBeFalse()
        ->and($status->canTransitionTo(IterationStatus::Planned))->toBeFalse();
})->with([
    'completed' => IterationStatus::Completed,
    'cancelled' => IterationStatus::Cancelled,
]);

it('freezes the plan once the cycle is over', function () {
    expect(IterationStatus::Planned->allowsModification())->toBeTrue()
        ->and(IterationStatus::InProgress->allowsModification())->toBeTrue()
        ->and(IterationStatus::Completed->allowsModification())->toBeFalse()
        ->and(IterationStatus::Cancelled->allowsModification())->toBeFalse();
});

/**
 * Design work and the plan share one window, unlike a playtest's conclusion which outlives its plan. That
 * is deliberate: a change recorded against a finished cycle is one nobody can date, and a cycle that
 * stayed open to work after concluding would make its own outcome unfalsifiable.
 */
it('closes the cycle to design work at exactly the moment it closes to edits', function (IterationStatus $status) {
    expect($status->allowsWork())->toBe($status->allowsModification());
})->with(IterationStatus::cases());

it('words every status and every move it can make', function (IterationStatus $status) {
    expect($status->label())->not->toBeEmpty()
        ->and($status->deniedReason())->not->toBeEmpty();

    foreach ($status->transitions() as $target) {
        expect($status->transitionLabelTo($target))->not->toBeEmpty();
    }
})->with(IterationStatus::cases());
