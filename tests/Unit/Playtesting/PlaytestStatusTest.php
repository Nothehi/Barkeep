<?php

use Modules\Playtesting\Domain\Enums\PlaytestStatus;

it('starts life as planned', function () {
    expect(PlaytestStatus::default())->toBe(PlaytestStatus::Planned);
});

it('allows exactly the moves an investigation can make', function (PlaytestStatus $from, array $expected) {
    expect($from->transitions())->toBe($expected);
})->with([
    'planned' => [PlaytestStatus::Planned, [PlaytestStatus::InProgress, PlaytestStatus::Completed, PlaytestStatus::Cancelled]],
    'in progress' => [PlaytestStatus::InProgress, [PlaytestStatus::Completed, PlaytestStatus::Cancelled]],
    'completed' => [PlaytestStatus::Completed, []],
    'cancelled' => [PlaytestStatus::Cancelled, []],
]);

/**
 * A designer who planned three sessions, ran none and decided the question was
 * answered anyway should not have to fake one. Whether there is any evidence
 * is a rule about evidence, checked by the command rather than by the matrix.
 */
it('lets a planned playtest be completed without passing through in progress', function () {
    expect(PlaytestStatus::Planned->canTransitionTo(PlaytestStatus::Completed))->toBeTrue();
});

it('treats both endings as final', function (PlaytestStatus $status) {
    expect($status->isTerminal())->toBeTrue()
        ->and($status->canTransitionTo(PlaytestStatus::InProgress))->toBeFalse()
        ->and($status->canTransitionTo(PlaytestStatus::Planned))->toBeFalse();
})->with([
    'completed' => PlaytestStatus::Completed,
    'cancelled' => PlaytestStatus::Cancelled,
]);

it('freezes the plan once the playtest is over', function () {
    expect(PlaytestStatus::Planned->allowsModification())->toBeTrue()
        ->and(PlaytestStatus::InProgress->allowsModification())->toBeTrue()
        ->and(PlaytestStatus::Completed->allowsModification())->toBeFalse()
        ->and(PlaytestStatus::Cancelled->allowsModification())->toBeFalse();
});

/**
 * The one field a completed playtest stays open to. Conclusions are written
 * after the sessions are over, so freezing this with the rest of the plan
 * would make the loop the module exists to support impossible to close.
 */
it('keeps a completed playtest open to its conclusion, and a cancelled one closed', function () {
    expect(PlaytestStatus::Completed->allowsAnalysis())->toBeTrue()
        ->and(PlaytestStatus::Cancelled->allowsAnalysis())->toBeFalse();
});

it('stops accepting sessions exactly when it stops accepting changes', function (PlaytestStatus $status) {
    expect($status->allowsSessions())->toBe($status->allowsModification());
})->with(PlaytestStatus::cases());

it('has a label and a reason for every status', function (PlaytestStatus $status) {
    expect($status->label())->not->toBeEmpty()
        ->and($status->deniedReason())->not->toBeEmpty();
})->with(PlaytestStatus::cases());

/**
 * The client renders the moves the server offers rather than keeping its own
 * copy of the matrix, so every pair the matrix allows has to arrive already
 * worded. Terminal statuses offer nothing, which is itself the right answer.
 */
it('words every move it can offer', function (PlaytestStatus $from) {
    $labels = array_map(
        fn (PlaytestStatus $target): string => $from->transitionLabelTo($target),
        $from->transitions(),
    );

    expect($labels)->toHaveCount(count($from->transitions()))
        ->and(array_filter($labels, fn (string $label): bool => $label === ''))->toBe([]);
})->with(PlaytestStatus::cases());
