<?php

use Modules\GameDesign\Domain\Exceptions\InvalidVersionNumber;
use Modules\GameDesign\Domain\ValueObjects\VersionNumber;

it('counts from one', function () {
    expect(VersionNumber::first()->value)->toBe(1)
        ->and(VersionNumber::first()->next()->value)->toBe(2);
});

it('refuses a number no game could have', function (int $value) {
    expect(fn () => VersionNumber::fromInt($value))->toThrow(InvalidVersionNumber::class);
})->with(['zero' => 0, 'negative' => -1, 'very negative' => PHP_INT_MIN]);

/**
 * A URL segment is only a version number if it is a plain run of digits.
 * Coercing "v1", "1.0" or "01" would make three URLs mean one version, which
 * is a worse answer than not finding it.
 */
it('reads a number out of a URL segment', function () {
    expect(VersionNumber::fromRouteSegment('1')?->value)->toBe(1)
        ->and(VersionNumber::fromRouteSegment('42')?->value)->toBe(42);
});

it('does not read a version number out of anything else', function (string $segment) {
    expect(VersionNumber::fromRouteSegment($segment))->toBeNull();
})->with([
    'a label' => 'v1',
    'a decimal' => '1.0',
    'padded' => '01',
    'zero' => '0',
    'negative' => '-1',
    'empty' => '',
    'a word' => 'latest',
    'trailing space' => '1 ',
]);

it('writes itself the way people read it', function () {
    expect(VersionNumber::fromInt(3)->label())->toBe('v3')
        ->and((string) VersionNumber::fromInt(3))->toBe('3');
});

it('compares two numbers by value', function () {
    expect(VersionNumber::fromInt(3)->equals(VersionNumber::fromInt(3)))->toBeTrue()
        ->and(VersionNumber::fromInt(3)->equals(VersionNumber::fromInt(4)))->toBeFalse();
});
