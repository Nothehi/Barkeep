<?php

use Modules\Playtesting\Domain\Enums\PlaytestSessionStatus;

it('starts life as planned', function () {
    expect(PlaytestSessionStatus::default())->toBe(PlaytestSessionStatus::Planned);
});

it('allows exactly the moves a sitting can make', function (PlaytestSessionStatus $from, array $expected) {
    expect($from->transitions())->toBe($expected);
})->with([
    'planned' => [PlaytestSessionStatus::Planned, [PlaytestSessionStatus::InProgress, PlaytestSessionStatus::Cancelled]],
    'in progress' => [PlaytestSessionStatus::InProgress, [PlaytestSessionStatus::Completed, PlaytestSessionStatus::Cancelled]],
    'completed' => [PlaytestSessionStatus::Completed, []],
    'cancelled' => [PlaytestSessionStatus::Cancelled, []],
]);

/**
 * Unlike the playtest above, a session may not be completed straight from
 * planned: "completed" asserts the game was played, and the timestamps that
 * make a session useful as evidence only exist because somebody started it.
 */
it('will not complete a session nobody started', function () {
    expect(PlaytestSessionStatus::Planned->canTransitionTo(PlaytestSessionStatus::Completed))->toBeFalse();
});

it('refuses to reclassify a finished sitting as cancelled', function () {
    expect(PlaytestSessionStatus::Completed->canTransitionTo(PlaytestSessionStatus::Cancelled))->toBeFalse();
});

it('cancels from either side of the start', function () {
    expect(PlaytestSessionStatus::Planned->canTransitionTo(PlaytestSessionStatus::Cancelled))->toBeTrue()
        ->and(PlaytestSessionStatus::InProgress->canTransitionTo(PlaytestSessionStatus::Cancelled))->toBeTrue();
});

it('closes an ended session to further evidence', function () {
    expect(PlaytestSessionStatus::Planned->allowsEvidence())->toBeTrue()
        ->and(PlaytestSessionStatus::InProgress->allowsEvidence())->toBeTrue()
        ->and(PlaytestSessionStatus::Completed->allowsEvidence())->toBeFalse()
        ->and(PlaytestSessionStatus::Cancelled->allowsEvidence())->toBeFalse();
});

it('counts only completed sittings as having happened', function (PlaytestSessionStatus $status) {
    expect($status->isConcluded())->toBe($status === PlaytestSessionStatus::Completed);
})->with(PlaytestSessionStatus::cases());

it('has a label and a reason for every status', function (PlaytestSessionStatus $status) {
    expect($status->label())->not->toBeEmpty()
        ->and($status->deniedReason())->not->toBeEmpty();
})->with(PlaytestSessionStatus::cases());
