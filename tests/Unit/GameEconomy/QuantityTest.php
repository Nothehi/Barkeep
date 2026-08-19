<?php

use Modules\GameEconomy\Domain\ValueObjects\Quantity;

/*
 * Every number in this module goes through Quantity, and the reason is that
 * floating point is wrong for this domain. These tests are the evidence: each
 * one is a case where a float would give an answer a designer would have to
 * report as a bug.
 */

it('adds in base ten, so a tenth plus two tenths is three tenths', function () {
    expect(Quantity::from('0.1')->plus(Quantity::from('0.2'))->label())->toBe('0.3');
});

it('keeps a threshold exact rather than nearly right', function () {
    $twenty = Quantity::from('20');

    expect($twenty->minus(Quantity::from('0.1'))->plus(Quantity::from('0.1'))->label())->toBe('20');
});

it('divides to the module\'s scale', function () {
    expect(Quantity::from(2)->dividedBy(Quantity::from(3))?->label())->toBe('0.666666');
});

it('answers null rather than raising when the divisor is zero', function () {
    expect(Quantity::from(2)->dividedBy(Quantity::zero()))->toBeNull();
});

it('reads a stored decimal back as the number somebody typed', function () {
    expect(Quantity::from('3.000000')->label())->toBe('3')
        ->and(Quantity::from('3.500000')->label())->toBe('3.5')
        ->and(Quantity::from('0.000000')->label())->toBe('0');
});

it('stores at full scale so a value round-trips', function () {
    expect(Quantity::from('3')->toStorage())->toBe('3.000000');
});

it('compares two representations of the same number as equal', function () {
    expect(Quantity::from('3')->equals(Quantity::from('3.000000')))->toBeTrue();
});

it('treats an absent bound as unbounded on that side', function () {
    $five = Quantity::from('5');

    expect($five->isWithin(Quantity::from('0'), null))->toBeTrue()
        ->and($five->isWithin(null, Quantity::from('4')))->toBeFalse()
        ->and($five->isWithin(null, null))->toBeTrue();
});

it('refuses anything that is not a plain decimal', function () {
    expect(Quantity::isValid('1e3'))->toBeFalse()
        ->and(Quantity::isValid('0x10'))->toBeFalse()
        ->and(Quantity::isValid('five'))->toBeFalse()
        ->and(Quantity::isValid('-2.5'))->toBeTrue();
});

it('tells an absent value from a zero one', function () {
    expect(Quantity::fromNullable(null))->toBeNull()
        ->and(Quantity::fromNullable(''))->toBeNull()
        ->and(Quantity::fromNullable('0')?->isZero())->toBeTrue();
});

it('turns a magnitude out of a negative without losing precision', function () {
    expect(Quantity::from('-2.125')->absolute()->label())->toBe('2.125');
});
