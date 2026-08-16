<?php

use Modules\Playtesting\Domain\Enums\ObservationCategory;
use Modules\Playtesting\Domain\Enums\PlaytestParticipantRole;

/**
 * The escape hatch that keeps the taxonomy honest while it is small: nobody is
 * ever forced to file something under the wrong heading.
 */
it('falls back to other rather than guessing', function () {
    expect(ObservationCategory::default())->toBe(ObservationCategory::Other);
});

it('stays small enough to pick from during a live session', function () {
    expect(ObservationCategory::cases())->toHaveCount(8);
});

it('has a label and an example for every category', function (ObservationCategory $category) {
    expect($category->label())->not->toBeEmpty()
        ->and($category->description())->not->toBeEmpty();
})->with(ObservationCategory::cases());

/**
 * Most people at a playtest are playing, and asking for the role before the
 * name would get in the way of the one thing the live screen has to be good
 * at: adding somebody quickly.
 */
it('assumes somebody at a playtest is playing', function () {
    expect(PlaytestParticipantRole::default())->toBe(PlaytestParticipantRole::Player);
});

it('knows which role was actually playing the game', function (PlaytestParticipantRole $role) {
    expect($role->isPlaying())->toBe($role === PlaytestParticipantRole::Player);
})->with(PlaytestParticipantRole::cases());

it('has a label and a description for every role', function (PlaytestParticipantRole $role) {
    expect($role->label())->not->toBeEmpty()
        ->and($role->description())->not->toBeEmpty();
})->with(PlaytestParticipantRole::cases());
