<?php

use Modules\GameDesign\Domain\Exceptions\InvalidMechanicSlug;
use Modules\GameDesign\Domain\ValueObjects\MechanicSlug;

it('accepts a well formed address', function (string $value) {
    expect(MechanicSlug::fromString($value)->value)->toBe($value);
})->with([
    'a word' => 'auction',
    'hyphenated' => 'worker-placement',
    'with digits' => 'area-control-2',
    'shortest allowed' => 'ai',
]);

/**
 * Case folding and trimming cannot change which term an address means, so they
 * are normalised. Everything else is refused rather than rewritten — the same
 * lossless-or-reject rule the game and workspace addresses follow.
 */
it('folds case and trims whitespace', function () {
    expect(MechanicSlug::fromString('  Worker-Placement  ')->value)->toBe('worker-placement');
});

it('refuses anything that would have to be rewritten', function (string $value) {
    expect(fn () => MechanicSlug::fromString($value))->toThrow(InvalidMechanicSlug::class);
})->with([
    'empty' => '',
    'blank' => '   ',
    'spaces' => 'worker placement',
    'underscores' => 'worker_placement',
    'accents' => 'auctión',
    'leading hyphen' => '-auction',
    'trailing hyphen' => 'auction-',
    'double hyphen' => 'worker--placement',
    'too short' => 'a',
]);

it('refuses an address longer than the maximum', function () {
    $tooLong = str_repeat('a', MechanicSlug::MAX_LENGTH + 1);

    expect(fn () => MechanicSlug::fromString($tooLong))->toThrow(InvalidMechanicSlug::class);
});

/**
 * A name is prose and an address is an identifier, so deriving one from the
 * other is allowed to rewrite. That is the whole difference between
 * `fromName()` and `fromString()`, and it is why curators type names.
 */
it('derives an address from a name', function (string $name, string $expected) {
    expect(MechanicSlug::fromName($name)->value)->toBe($expected);
})->with([
    'title case' => ['Worker Placement', 'worker-placement'],
    'punctuation' => ['Push your luck!', 'push-your-luck'],
    'extra spacing' => ['  Set   collection  ', 'set-collection'],
    'accents folded' => ['Auctión', 'auction'],
]);

it('refuses a name with nothing sluggable in it', function (string $name) {
    expect(fn () => MechanicSlug::fromName($name))->toThrow(InvalidMechanicSlug::class);
})->with([
    'punctuation only' => '!!!',
    'empty' => '',
    'one letter' => 'a',
]);

it('truncates a long name at the maximum', function () {
    $slug = MechanicSlug::fromName(str_repeat('word ', 40));

    expect(mb_strlen($slug->value))->toBeLessThanOrEqual(MechanicSlug::MAX_LENGTH)
        ->and($slug->value)->not->toEndWith('-');
});

/**
 * How `MechanicSlugAllocator` resolves a collision. The suffix has to fit
 * inside the maximum, which means the base gets shortened rather than the
 * result overflowing.
 */
it('appends a suffix without overflowing the maximum', function () {
    $base = MechanicSlug::fromString(str_repeat('a', MechanicSlug::MAX_LENGTH));
    $suffixed = $base->withSuffix(2);

    expect($suffixed->value)->toEndWith('-2')
        ->and(mb_strlen($suffixed->value))->toBeLessThanOrEqual(MechanicSlug::MAX_LENGTH);
});

it('answers whether a value would be valid without raising', function () {
    expect(MechanicSlug::isValid('worker-placement'))->toBeTrue()
        ->and(MechanicSlug::isValid('worker placement'))->toBeFalse()
        ->and(MechanicSlug::isValid(''))->toBeFalse();
});

it('compares two addresses by value', function () {
    expect(MechanicSlug::fromString('auction')->equals(MechanicSlug::fromString('auction')))->toBeTrue()
        ->and(MechanicSlug::fromString('auction')->equals(MechanicSlug::fromString('drafting')))->toBeFalse();
});

it('renders as its value', function () {
    expect((string) MechanicSlug::fromString('worker-placement'))->toBe('worker-placement');
});
