<?php

use Modules\DesignFramework\Domain\Exceptions\InvalidFrameworkVersionNumber;
use Modules\DesignFramework\Domain\ValueObjects\FrameworkVersionNumber;

it('numbers the first edition one', function () {
    expect(FrameworkVersionNumber::first()->value)->toBe(1);
});

it('refuses a number below one', function (int $value) {
    expect(fn () => FrameworkVersionNumber::fromInt($value))->toThrow(InvalidFrameworkVersionNumber::class);
})->with([0, -1]);

it('counts forwards', function () {
    expect(FrameworkVersionNumber::first()->next()->value)->toBe(2);
});

it('writes a version the way designers cite it', function () {
    expect(FrameworkVersionNumber::fromInt(3)->label())->toBe('v3')
        ->and((string) FrameworkVersionNumber::fromInt(3))->toBe('3');
});

/**
 * A URL segment is only a version number if it is a plain run of digits. "v1", "1.0" and "01" all name
 * nothing rather than being coerced into something — which keeps a mistyped address a 404 instead of a
 * surprise.
 */
it('reads only a plain run of digits out of a URL', function (string $segment, ?int $expected) {
    expect(FrameworkVersionNumber::fromRouteSegment($segment)?->value)->toBe($expected);
})->with([
    ['1', 1],
    ['12', 12],
    ['v1', null],
    ['1.0', null],
    ['01', null],
    ['0', null],
    ['', null],
    ['-1', null],
]);
