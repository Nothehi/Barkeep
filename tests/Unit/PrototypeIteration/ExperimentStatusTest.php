<?php

use Modules\PrototypeIteration\Domain\Enums\ExperimentStatus;

it('starts life as planned', function () {
    expect(ExperimentStatus::default())->toBe(ExperimentStatus::Planned);
});

it('allows exactly the moves an experiment can make', function (ExperimentStatus $from, array $expected) {
    expect($from->transitions())->toBe($expected);
})->with([
    'planned' => [ExperimentStatus::Planned, [ExperimentStatus::Running, ExperimentStatus::Cancelled]],
    'running' => [ExperimentStatus::Running, [ExperimentStatus::Completed, ExperimentStatus::Cancelled]],
    'completed' => [ExperimentStatus::Completed, []],
    'cancelled' => [ExperimentStatus::Cancelled, []],
]);

/**
 * An experiment's value is its actual result, and an experiment nobody ran has none — so a question that
 * was dropped gets cancelled, which says so honestly.
 */
it('refuses to complete an experiment that was never run', function () {
    expect(ExperimentStatus::Planned->canTransitionTo(ExperimentStatus::Completed))->toBeFalse();
});

/**
 * The window in which a prediction may still be edited closes when the experiment does. Editing a
 * hypothesis after the result is known is how it becomes retroactively correct.
 */
it('freezes the design once the experiment is answered', function () {
    expect(ExperimentStatus::Planned->allowsModification())->toBeTrue()
        ->and(ExperimentStatus::Running->allowsModification())->toBeTrue()
        ->and(ExperimentStatus::Completed->allowsModification())->toBeFalse()
        ->and(ExperimentStatus::Cancelled->allowsModification())->toBeFalse();
});

it('treats both endings as final', function (ExperimentStatus $status) {
    expect($status->isTerminal())->toBeTrue();
})->with([
    'completed' => ExperimentStatus::Completed,
    'cancelled' => ExperimentStatus::Cancelled,
]);

it('words every status and every move it can make', function (ExperimentStatus $status) {
    expect($status->label())->not->toBeEmpty()
        ->and($status->deniedReason())->not->toBeEmpty();

    foreach ($status->transitions() as $target) {
        expect($status->transitionLabelTo($target))->not->toBeEmpty();
    }
})->with(ExperimentStatus::cases());
