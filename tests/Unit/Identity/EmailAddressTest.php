<?php

use Modules\Identity\Domain\Exceptions\InvalidEmailAddress;
use Modules\Identity\Domain\ValueObjects\EmailAddress;

test('addresses are normalised to lowercase and trimmed', function () {
    expect(EmailAddress::fromString('  Designer@Barkeep.TEST  ')->value)
        ->toBe('designer@barkeep.test');
});

test('addresses differing only by case and padding are equal', function () {
    expect(EmailAddress::fromString('Designer@Barkeep.test'))
        ->toEqual(EmailAddress::fromString(' designer@barkeep.TEST '));

    expect(EmailAddress::fromString('Designer@Barkeep.test')->equals(
        EmailAddress::fromString(' designer@barkeep.TEST '),
    ))->toBeTrue();
});

test('an address renders as its normalised string', function () {
    expect((string) EmailAddress::fromString('Designer@Barkeep.test'))
        ->toBe('designer@barkeep.test');
});

test('malformed addresses are rejected', function (string $value) {
    expect(fn () => EmailAddress::fromString($value))
        ->toThrow(InvalidEmailAddress::class);
})->with([
    '',
    '   ',
    'not-an-email',
    'missing@domain',
    '@barkeep.test',
]);
