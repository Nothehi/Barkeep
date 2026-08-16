<?php

use Modules\GameDesign\Domain\Enums\GameStatus;

/**
 * The transition matrix is the module's lifecycle rule, and it is stated in
 * one place so that it can be tested in one place.
 */
it('starts a game as a draft', function () {
    expect(GameStatus::default())->toBe(GameStatus::Draft);
});

it('allows exactly the moves the lifecycle describes', function (GameStatus $from, array $expected) {
    expect($from->transitions())->toEqualCanonicalizing($expected);
})->with([
    'draft' => [GameStatus::Draft, [GameStatus::Active, GameStatus::Archived]],
    'active' => [GameStatus::Active, [GameStatus::OnHold, GameStatus::Completed, GameStatus::Archived]],
    'on hold' => [GameStatus::OnHold, [GameStatus::Active, GameStatus::Archived]],
    'completed' => [GameStatus::Completed, [GameStatus::Archived]],
    'archived' => [GameStatus::Archived, []],
]);

it('agrees with its own matrix about what is allowed', function () {
    foreach (GameStatus::cases() as $from) {
        foreach (GameStatus::cases() as $to) {
            expect($from->canTransitionTo($to))
                ->toBe(in_array($to, $from->transitions(), strict: true));
        }
    }
});

/**
 * A game cannot move to the state it is already in. Callers treat that as a
 * no-op rather than as an error, but the matrix itself should not pretend it
 * is a move.
 */
it('does not describe standing still as a transition', function (GameStatus $status) {
    expect($status->canTransitionTo($status))->toBeFalse();
})->with(GameStatus::cases());

/**
 * Every path out of a game's life ends at archived, and archived ends there.
 * If a future status were added with no way to reach archival, this fails.
 */
it('leaves every status a way to be archived, and no way back', function () {
    foreach (GameStatus::cases() as $status) {
        if ($status === GameStatus::Archived) {
            expect($status->isTerminal())->toBeTrue();

            continue;
        }

        expect($status->canTransitionTo(GameStatus::Archived))->toBeTrue()
            ->and($status->isTerminal())->toBeFalse();
    }
});

it('treats every status except archived as still editable', function (GameStatus $status) {
    expect($status->allowsModification())->toBe($status !== GameStatus::Archived);
})->with(GameStatus::cases());

it('has a label for every status', function (GameStatus $status) {
    expect($status->label())->not->toBe('')
        ->and($status->deniedReason())->not->toBe('');
})->with(GameStatus::cases());

/**
 * The wording depends on both ends of the move: reaching active from draft is
 * starting work, reaching it from on hold is picking it back up.
 */
it('words a move by where it starts as well as where it ends', function () {
    expect(GameStatus::Draft->transitionLabelTo(GameStatus::Active))
        ->not->toBe(GameStatus::OnHold->transitionLabelTo(GameStatus::Active));
});

it('has wording for every move it permits', function () {
    foreach (GameStatus::cases() as $from) {
        foreach ($from->transitions() as $to) {
            expect($from->transitionLabelTo($to))->not->toBe('');
        }
    }
});
