<?php

use Modules\GameDesign\Domain\Enums\DesignPhase;

it('starts a game at the idea', function () {
    expect(DesignPhase::default())->toBe(DesignPhase::Idea);
});

/**
 * The order exists so progress can be drawn. It is never enforced — a game
 * dropping back from playtesting to prototyping is the normal thing — but it
 * has to be a genuine sequence with no gaps or repeats, because the interface
 * renders "step N of M" from it.
 */
it('numbers the phases as an unbroken sequence', function () {
    $positions = array_map(
        fn (DesignPhase $phase): int => $phase->position(),
        DesignPhase::cases(),
    );

    expect($positions)->toBe(range(1, DesignPhase::count()))
        ->and(DesignPhase::count())->toBe(count(DesignPhase::cases()));
});

it('reads in the order a game is designed', function () {
    expect(DesignPhase::Idea->position())->toBeLessThan(DesignPhase::Concept->position())
        ->and(DesignPhase::Prototyping->position())->toBeLessThan(DesignPhase::Playtesting->position())
        ->and(DesignPhase::Production->position())->toBeLessThan(DesignPhase::Published->position());
});

it('explains every phase to somebody who has not read a framework', function (DesignPhase $phase) {
    expect($phase->label())->not->toBe('')
        ->and($phase->description())->not->toBe('');
})->with(DesignPhase::cases());
