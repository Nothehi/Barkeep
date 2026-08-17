<?php

use Modules\DesignFramework\Domain\Enums\GameFrameworkStatus;

it('starts life active', function () {
    expect(GameFrameworkStatus::default())->toBe(GameFrameworkStatus::Active);
});

/**
 * Pausing exists so that stopping can be honest, and resuming exists so that pausing is not a one-way
 * door. Without the pair, a studio stepping away for a month either leaves its progress bars claiming
 * active work or completes a framework it barely started.
 */
it('lets a paused adoption be picked back up', function () {
    expect(GameFrameworkStatus::Active->canTransitionTo(GameFrameworkStatus::Paused))->toBeTrue()
        ->and(GameFrameworkStatus::Paused->canTransitionTo(GameFrameworkStatus::Active))->toBeTrue();
});

it('treats completion as terminal', function () {
    expect(GameFrameworkStatus::Completed->transitions())->toBe([])
        ->and(GameFrameworkStatus::Completed->isTerminal())->toBeTrue();
});

it('only accepts new work while active', function () {
    expect(GameFrameworkStatus::Active->allowsProgress())->toBeTrue()
        ->and(GameFrameworkStatus::Paused->allowsProgress())->toBeFalse()
        ->and(GameFrameworkStatus::Completed->allowsProgress())->toBeFalse();
});

/**
 * Reaching Active from Paused is resuming, not "making active". The wording depends on both ends, which
 * is why it lives beside the matrix that allows the move.
 */
it('words resuming differently from activating', function () {
    expect(GameFrameworkStatus::Paused->transitionLabelTo(GameFrameworkStatus::Active))
        ->not->toBe(GameFrameworkStatus::Completed->transitionLabelTo(GameFrameworkStatus::Active));
});

it('words every status and every move it can make', function (GameFrameworkStatus $status) {
    expect($status->label())->not->toBe('')
        ->and($status->deniedReason())->not->toBe('');

    foreach ($status->transitions() as $target) {
        expect($status->transitionLabelTo($target))->not->toBe('');
    }
})->with(GameFrameworkStatus::cases());
