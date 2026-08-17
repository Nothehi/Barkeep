<?php

use Modules\GameDesign\Domain\Enums\Complexity;

it('runs from lightest to heaviest without a gap', function () {
    $positions = array_map(fn (Complexity $c): int => $c->position(), Complexity::cases());

    expect($positions)->toBe(range(1, Complexity::count()));
});

it('knows how many steps the scale has', function () {
    expect(Complexity::count())->toBe(5);
});

it('labels every step', function (Complexity $complexity) {
    expect($complexity->label())->not->toBe('');
})->with(Complexity::cases());

/**
 * The description is written in terms of what the table has to do, because that
 * is what the designer is deciding. "Medium complexity" would send them back to
 * guessing what the scale means.
 */
it('describes every step in terms of the table', function (Complexity $complexity) {
    expect($complexity->description())->not->toBe('')
        ->and($complexity->description())->not->toContain('complexity');
})->with(Complexity::cases());

it('has no default', function () {
    /*
     * Deliberately unlike DesignPhase and GameStatus, which both start
     * somewhere. A weight nobody has chosen is not "party" — it is undecided,
     * and the record stores that as null so a methodology can notice.
     */
    expect(method_exists(Complexity::class, 'default'))->toBeFalse();
});
