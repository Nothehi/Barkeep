<?php

use Modules\PrototypeIteration\Domain\Enums\PrototypeStatus;

it('starts life as a draft', function () {
    expect(PrototypeStatus::default())->toBe(PrototypeStatus::Draft);
});

it('allows exactly the moves a prototype can make', function (PrototypeStatus $from, array $expected) {
    expect($from->transitions())->toBe($expected);
})->with([
    'draft' => [PrototypeStatus::Draft, [PrototypeStatus::Active, PrototypeStatus::Archived]],
    'active' => [PrototypeStatus::Active, [PrototypeStatus::Archived]],
    'archived' => [PrototypeStatus::Archived, []],
]);

/**
 * A prototype somebody started assembling and then abandoned is a real outcome, and should not have to be
 * activated first in order to be put away.
 */
it('lets a draft be archived without being activated', function () {
    expect(PrototypeStatus::Draft->canTransitionTo(PrototypeStatus::Archived))->toBeTrue();
});

/**
 * Archived is terminal. A prototype accumulates versions and those versions accumulate iterations, so
 * un-archiving one would make "we stopped working on this in March" a field somebody can quietly flip.
 */
it('never comes back from archived', function () {
    expect(PrototypeStatus::Archived->isTerminal())->toBeTrue()
        ->and(PrototypeStatus::Archived->canTransitionTo(PrototypeStatus::Active))->toBeFalse()
        ->and(PrototypeStatus::Archived->canTransitionTo(PrototypeStatus::Draft))->toBeFalse();
});

it('closes an archived prototype to edits and to new versions', function () {
    expect(PrototypeStatus::Archived->allowsModification())->toBeFalse()
        ->and(PrototypeStatus::Archived->allowsVersions())->toBeFalse();
});

it('keeps an open prototype open to both', function (PrototypeStatus $status) {
    expect($status->allowsModification())->toBeTrue()
        ->and($status->allowsVersions())->toBeTrue();
})->with([
    'draft' => PrototypeStatus::Draft,
    'active' => PrototypeStatus::Active,
]);

it('words every status and every move it can make', function (PrototypeStatus $status) {
    expect($status->label())->not->toBeEmpty()
        ->and($status->deniedReason())->not->toBeEmpty();

    foreach ($status->transitions() as $target) {
        expect($status->transitionLabelTo($target))->not->toBeEmpty();
    }
})->with(PrototypeStatus::cases());
