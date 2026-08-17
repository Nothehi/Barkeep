<?php

use Modules\GameDesign\Domain\Exceptions\InvalidPlayerCount;
use Modules\GameDesign\Domain\ValueObjects\PlayerCountRange;

it('accepts a range', function () {
    $range = PlayerCountRange::fromInts(2, 4);

    expect($range->min)->toBe(2)
        ->and($range->max)->toBe(4)
        ->and($range->isFixed())->toBeFalse();
});

it('accepts a fixed count', function () {
    expect(PlayerCountRange::fromInts(2, 2)->isFixed())->toBeTrue();
});

/**
 * The rule lives here rather than in a validation rule, so a console command or
 * a seeder is held to it too. "4 to 2 players" is two boxes filled in the wrong
 * order, and it gets its own message rather than a restatement of the bounds.
 */
it('refuses a range that runs backwards', function () {
    expect(fn () => PlayerCountRange::fromInts(4, 2))
        ->toThrow(InvalidPlayerCount::class, 'wrong way round');
});

it('refuses a count below the minimum', function (int $min, int $max) {
    expect(fn () => PlayerCountRange::fromInts($min, $max))->toThrow(InvalidPlayerCount::class);
})->with([
    'nobody' => [0, 4],
    'negative' => [-1, 4],
    'upper end too low' => [1, 0],
]);

it('refuses a count above the maximum', function () {
    expect(fn () => PlayerCountRange::fromInts(1, PlayerCountRange::MAXIMUM + 1))
        ->toThrow(InvalidPlayerCount::class);
});

/**
 * Solitaire is a real thing designers build, which is why the floor is one
 * rather than two.
 */
it('accepts a solitaire game', function () {
    expect(PlayerCountRange::fromInts(1, 1)->label())->toBe('Solitaire');
});

it('is nothing when neither end is given', function () {
    expect(PlayerCountRange::fromNullableInts(null, null))->toBeNull();
});

/**
 * Somebody typing into one box and leaving the other alone means a fixed count.
 * Making them type it twice would be pedantry.
 */
it('reads one end on its own as a fixed count', function (?int $min, ?int $max) {
    $range = PlayerCountRange::fromNullableInts($min, $max);

    expect($range->isFixed())->toBeTrue()
        ->and($range->min)->toBe(3);
})->with([
    'lower only' => [3, null],
    'upper only' => [null, 3],
]);

it('labels a range the way people say it', function () {
    expect(PlayerCountRange::fromInts(2, 4)->label())->toBe('2 to 4 players')
        ->and(PlayerCountRange::fromInts(4, 4)->label())->toBe('4 players');
});

it('answers whether it covers a given number of players', function () {
    $range = PlayerCountRange::fromInts(2, 4);

    expect($range->includes(3))->toBeTrue()
        ->and($range->includes(2))->toBeTrue()
        ->and($range->includes(4))->toBeTrue()
        ->and($range->includes(1))->toBeFalse()
        ->and($range->includes(5))->toBeFalse();
});

it('compares two ranges by value', function () {
    expect(PlayerCountRange::fromInts(2, 4)->equals(PlayerCountRange::fromInts(2, 4)))->toBeTrue()
        ->and(PlayerCountRange::fromInts(2, 4)->equals(PlayerCountRange::fromInts(2, 5)))->toBeFalse();
});

it('renders compactly as a string', function () {
    expect((string) PlayerCountRange::fromInts(2, 4))->toBe('2-4')
        ->and((string) PlayerCountRange::fromInts(3, 3))->toBe('3');
});
