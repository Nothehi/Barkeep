<?php

use Modules\DesignFramework\Domain\Exceptions\InvalidPosition;
use Modules\DesignFramework\Domain\ValueObjects\Position;

it('counts from one, because people read the numbers', function () {
    expect(Position::first()->value)->toBe(1)
        ->and(Position::FIRST)->toBe(1);
});

it('refuses a position below one', function (int $value) {
    expect(fn () => Position::fromInt($value))->toThrow(InvalidPosition::class);
})->with([0, -1, -100]);

it('appends after a set of the given size', function () {
    expect(Position::afterCount(0)->value)->toBe(1)
        ->and(Position::afterCount(4)->value)->toBe(5);
});

it('treats a negative count as an empty set', function () {
    expect(Position::afterCount(-3)->value)->toBe(1);
});

it('accepts a move to any place inside the list, including the last', function () {
    expect(Position::within(1, 4)->value)->toBe(1)
        ->and(Position::within(4, 4)->value)->toBe(4);
});

/**
 * Refusing rather than clamping is deliberate. A clamp turns a drag that landed in the wrong place into
 * a reorder nobody asked for, silently — and silence is what makes it hard to notice.
 */
it('refuses a move past the end of the list', function () {
    expect(fn () => Position::within(9, 4))->toThrow(InvalidPosition::class);
});

it('reports a bad position against the field the form submitted', function () {
    $refusal = null;

    try {
        Position::within(0, 4);
    } catch (InvalidPosition $caught) {
        $refusal = $caught;
    }

    expect($refusal?->field())->toBe('position');
});

it('allows the first position in an empty list', function () {
    expect(Position::within(1, 0)->value)->toBe(1);
});

it('compares by value', function () {
    expect(Position::fromInt(3)->equals(Position::fromInt(3)))->toBeTrue()
        ->and(Position::fromInt(3)->equals(Position::fromInt(4)))->toBeFalse();
});
