<?php

use Modules\GameDesign\Domain\Exceptions\InvalidPlayTime;
use Modules\GameDesign\Domain\ValueObjects\PlayTimeRange;

it('accepts a range of minutes', function () {
    $range = PlayTimeRange::fromMinutes(45, 60);

    expect($range->min)->toBe(45)
        ->and($range->max)->toBe(60)
        ->and($range->isFixed())->toBeFalse();
});

it('refuses a range that runs backwards', function () {
    expect(fn () => PlayTimeRange::fromMinutes(90, 30))
        ->toThrow(InvalidPlayTime::class, 'wrong way round');
});

it('refuses a length below the minimum', function () {
    expect(fn () => PlayTimeRange::fromMinutes(0, 30))->toThrow(InvalidPlayTime::class);
});

/**
 * Past a day somebody is describing a campaign made of sessions, and a
 * campaign's length is not one game's playing time.
 */
it('refuses a length above the maximum', function () {
    expect(fn () => PlayTimeRange::fromMinutes(1, PlayTimeRange::MAXIMUM + 1))
        ->toThrow(InvalidPlayTime::class);
});

it('accepts a micro game', function () {
    expect(PlayTimeRange::fromMinutes(1, 1)->label())->toBe('1 min');
});

it('is nothing when neither end is given', function () {
    expect(PlayTimeRange::fromNullableMinutes(null, null))->toBeNull();
});

it('reads one end on its own as a fixed length', function (?int $min, ?int $max) {
    expect(PlayTimeRange::fromNullableMinutes($min, $max)->isFixed())->toBeTrue();
})->with([
    'lower only' => [45, null],
    'upper only' => [null, 45],
]);

/**
 * Minutes are stored and hours are rendered, in exactly one place. Two screens
 * disagreeing about whether ninety minutes is "90 min" or "1 h 30 min" is the
 * failure this centralisation exists to prevent.
 */
it('renders minutes the way people say them', function (int $minutes, string $expected) {
    expect(PlayTimeRange::fromMinutes($minutes, $minutes)->label())->toBe($expected);
})->with([
    'under an hour' => [45, '45 min'],
    'one minute' => [1, '1 min'],
    'fifty nine' => [59, '59 min'],
    'exactly an hour' => [60, '1 h'],
    'and a half' => [90, '1 h 30 min'],
    'three hours' => [180, '3 h'],
    'a whole day' => [1440, '24 h'],
]);

it('labels a range with both ends rendered', function () {
    expect(PlayTimeRange::fromMinutes(45, 90)->label())->toBe('45 min to 1 h 30 min');
});

it('compares two ranges by value', function () {
    expect(PlayTimeRange::fromMinutes(45, 60)->equals(PlayTimeRange::fromMinutes(45, 60)))->toBeTrue()
        ->and(PlayTimeRange::fromMinutes(45, 60)->equals(PlayTimeRange::fromMinutes(45, 90)))->toBeFalse();
});

it('renders compactly as a string', function () {
    expect((string) PlayTimeRange::fromMinutes(45, 60))->toBe('45-60')
        ->and((string) PlayTimeRange::fromMinutes(45, 45))->toBe('45');
});
