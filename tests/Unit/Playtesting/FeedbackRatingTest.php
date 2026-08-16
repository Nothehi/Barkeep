<?php

use Modules\Playtesting\Domain\Exceptions\InvalidFeedbackRating;
use Modules\Playtesting\Domain\ValueObjects\FeedbackRating;

it('accepts every point on the scale', function (int $value) {
    expect(FeedbackRating::fromInt($value)->value)->toBe($value);
})->with([1, 2, 3, 4, 5]);

it('refuses anything off the scale', function (int $value) {
    expect(fn () => FeedbackRating::fromInt($value))->toThrow(InvalidFeedbackRating::class);
})->with([
    'zero' => 0,
    'six' => 6,
    'negative' => -1,
    'absurd' => 100,
]);

/**
 * "No rating" is an answer rather than a missing one: a participant who
 * commented without scoring did not score the game badly.
 */
it('keeps the absence of a rating as an absence', function () {
    expect(FeedbackRating::fromNullable(null))->toBeNull()
        ->and(FeedbackRating::fromNullable(3)?->value)->toBe(3);
});

it('answers whether a number is on the scale without throwing', function () {
    expect(FeedbackRating::isValid(1))->toBeTrue()
        ->and(FeedbackRating::isValid(5))->toBeTrue()
        ->and(FeedbackRating::isValid(0))->toBeFalse()
        ->and(FeedbackRating::isValid(6))->toBeFalse();
});

it('publishes the whole scale so a screen does not have to know it', function () {
    expect(FeedbackRating::scale())->toBe([1, 2, 3, 4, 5]);
});

it('writes itself the way people read it', function () {
    expect(FeedbackRating::fromInt(4)->label())->toBe('4/5');
});

it('reports the field the error belongs to, so a form can show it in place', function () {
    expect(InvalidFeedbackRating::outOfRange(9)->field())->toBe('rating');
});
