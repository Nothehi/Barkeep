<?php

use Modules\PrototypeIteration\Domain\Exceptions\InvalidPrototypeVersionNumber;
use Modules\PrototypeIteration\Domain\ValueObjects\PrototypeVersionNumber;

it('counts from one', function () {
    expect(PrototypeVersionNumber::first()->value)->toBe(1);
});

it('refuses a number a version cannot have', function (int $value) {
    expect(fn () => PrototypeVersionNumber::fromInt($value))
        ->toThrow(InvalidPrototypeVersionNumber::class);
})->with([0, -1, -999]);

it('follows on from itself', function () {
    expect(PrototypeVersionNumber::fromInt(3)->next()->value)->toBe(4);
});

it('is written the way a designer says it', function () {
    expect(PrototypeVersionNumber::fromInt(4)->label())->toBe('v4')
        ->and((string) PrototypeVersionNumber::fromInt(4))->toBe('4');
});

it('compares by value', function () {
    expect(PrototypeVersionNumber::fromInt(2)->equals(PrototypeVersionNumber::fromInt(2)))->toBeTrue()
        ->and(PrototypeVersionNumber::fromInt(2)->equals(PrototypeVersionNumber::fromInt(3)))->toBeFalse();
});

it('reads a plain run of digits out of a URL segment', function () {
    expect(PrototypeVersionNumber::fromRouteSegment('12')?->value)->toBe(12);
});

/**
 * Returning null rather than raising is what lets a route binding turn a mistyped URL into a 404 instead of
 * a 500, and refusing anything but plain digits is what stops "v1" or "01" being coerced into a version
 * that reads differently from the one in the address.
 */
it('treats anything but plain digits as no such version', function (string $segment) {
    expect(PrototypeVersionNumber::fromRouteSegment($segment))->toBeNull();
})->with(['v1', '01', '1.0', '0', '-1', '', ' 1', '1 ', 'one', '1e3']);
